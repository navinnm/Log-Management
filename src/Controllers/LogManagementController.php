<?php

namespace Fulgid\LogManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Services\LogFilterService;
use Fulgid\LogManagement\Models\LogEntry;
use Fulgid\LogManagement\Models\NotificationSetting;

class LogManagementController extends Controller
{
    protected LogNotifierService $notifierService;
    protected LogStreamService $streamService;
    protected LogFilterService $filterService;

    public function __construct(
        LogNotifierService $notifierService,
        LogStreamService $streamService,
        LogFilterService $filterService
    ) {
        $this->notifierService = $notifierService;
        $this->streamService = $streamService;
        $this->filterService = $filterService;
    }

    /**
     * Display the log management dashboard.
     */
    public function dashboard(Request $request)
    {
        if (!$this->hasAccess($request)) {
            abort(403, 'Unauthorized access to log management');
        }

        $stats = [
            'total_logs' => LogEntry::count(),
            'logs_today' => LogEntry::whereDate('created_at', today())->count(),
            'logs_last_hour' => LogEntry::where('created_at', '>=', now()->subHour())->count(),
            'error_logs_today' => LogEntry::whereDate('created_at', today())
                ->whereIn('level', ['error', 'critical', 'emergency'])
                ->count(),
        ];

        $recentLogs = LogEntry::with([])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $levelStats = LogEntry::selectRaw('level, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDay())
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        return view('log-management::dashboard', compact('stats', 'recentLogs', 'levelStats'));
    }

    /**
     * Test notification channels.
     */
    public function testNotification(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'channel' => 'required|string',
            'message' => 'string',
        ]);

        try {
            $message = $request->get('message', 'Test notification from Log Management Package');
            
            $this->notifierService->notify($message, 'info', [
                'test' => true,
                'timestamp' => now()->toISOString(),
                'triggered_by' => auth()->user()->email ?? 'system',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification settings.
     */
    public function getNotificationSettings(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $settings = NotificationSetting::all()->keyBy('channel');

        return response()->json([
            'settings' => $settings,
            'available_channels' => array_keys($this->notifierService->getChannels()),
        ]);
    }

    /**
     * Update notification settings.
     */
    public function updateNotificationSettings(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'channel' => 'required|string',
            'enabled' => 'required|boolean',
            'settings' => 'array',
        ]);

        $notificationSetting = NotificationSetting::updateOrCreate(
            ['channel' => $request->channel],
            [
                'enabled' => $request->enabled,
                'settings' => $request->settings ?? [],
            ]
        );

        return response()->json([
            'success' => true,
            'setting' => $notificationSetting,
        ]);
    }

    /**
     * Clear old log entries.
     */
    public function clearLogs(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'older_than_days' => 'integer|min:1|max:365',
            'level' => 'string|in:debug,info,notice,warning,error,critical,alert,emergency',
        ]);

        $query = LogEntry::query();

        if ($request->has('older_than_days')) {
            $query->where('created_at', '<', now()->subDays($request->older_than_days));
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        $deletedCount = $query->count();
        $query->delete();

        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} log entries",
            'deleted_count' => $deletedCount,
        ]);
    }

    /**
     * Export logs.
     */
    public function exportLogs(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'format' => 'required|in:json,csv',
            'from' => 'date',
            'to' => 'date',
            'level' => 'string',
            'limit' => 'integer|max:10000',
        ]);

        $query = LogEntry::query();

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        $limit = $request->get('limit', 1000);
        $logs = $query->orderBy('created_at', 'desc')->limit($limit)->get();

        $filename = 'logs_' . now()->format('Y-m-d_H-i-s');

        if ($request->format === 'csv') {
            return $this->exportAsCsv($logs, $filename);
        }

        return $this->exportAsJson($logs, $filename);
    }

    /**
     * Get system information.
     */
    public function systemInfo(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'package_version' => '1.0.0', // This should be dynamic
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
            'environment' => app()->environment(),
            'config' => [
                'enabled' => config('log-management.enabled'),
                'notifications_enabled' => config('log-management.notifications.enabled'),
                'sse_enabled' => config('log-management.sse.enabled'),
                'database_enabled' => config('log-management.database.enabled'),
            ],
            'stats' => [
                'notifier' => $this->notifierService->getStats(),
                'stream' => $this->streamService->getStats(),
            ],
        ]);
    }

    /**
     * Export logs as CSV.
     */
    protected function exportAsCsv($logs, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, ['ID', 'Level', 'Message', 'Channel', 'Context', 'Created At']);
            
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->level,
                    $log->message,
                    $log->channel,
                    $log->context,
                    $log->created_at->toISOString(),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export logs as JSON.
     */
    protected function exportAsJson($logs, string $filename)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
        ];

        return response()->json([
            'exported_at' => now()->toISOString(),
            'total_records' => $logs->count(),
            'logs' => $logs->toArray(),
        ])->withHeaders($headers);
    }

    /**
     * Check if request has access to log management.
     */
    protected function hasAccess(Request $request): bool
    {
        // If authentication is disabled, allow access
        if (!config('log-management.auth.enabled', false)) {
            return true;
        }

        // Check for API key
        $apiKey = $request->header('X-Log-Management-Key') ?? $request->query('key');
        $validKeys = config('log-management.auth.api_keys', []);

        if ($apiKey && in_array($apiKey, $validKeys)) {
            return true;
        }

        // Check for authenticated user with proper permissions
        if (auth()->check()) {
            $user = auth()->user();
            $requiredPermission = config('log-management.auth.permission', 'manage-logs');

            // If no specific permission method exists, allow authenticated users
            if (!method_exists($user, 'can')) {
                return true;
            }

            return $user->can($requiredPermission);
        }

        return false;
    }
}