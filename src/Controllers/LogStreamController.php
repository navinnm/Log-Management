<?php

namespace Fulgid\LogManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Models\LogEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogStreamController extends Controller
{
    protected LogStreamService $streamService;

    public function __construct(LogStreamService $streamService)
    {
        $this->streamService = $streamService;
    }

    /**
     * Stream logs via Server-Sent Events.
     */
    public function stream(Request $request): StreamedResponse
    {
        // Validate access
        if (!$this->hasAccess($request)) {
            abort(403, 'Unauthorized access to log stream');
        }

        // Get filters from request
        $filters = $this->getFiltersFromRequest($request);

        return new StreamedResponse(function () use ($filters) {
            // Set up the stream
            $this->setupStream();

            // Get initial log entries if requested
            if (request('include_recent', false)) {
                $this->sendRecentLogs($filters);
            }

            // Keep connection alive and listen for new logs
            $this->maintainConnection();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * Get log entries for initial load or pagination.
     */
    public function getLogs(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = LogEntry::query();

        // Apply filters
        if ($request->has('level')) {
            $levels = is_array($request->level) ? $request->level : [$request->level];
            $query->whereIn('level', $levels);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('search')) {
            $query->where('message', 'like', '%' . $request->search . '%');
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ]
        ]);
    }

    /**
     * Get a single log entry by ID.
     */
    public function getLog(Request $request, $id)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $log = LogEntry::find($id);

        if (!$log) {
            return response()->json(['error' => 'Log entry not found'], 404);
        }

        return response()->json([
            'id' => $log->id,
            'level' => $log->level,
            'channel' => $log->channel,
            'message' => $log->message,
            'context' => $log->context,
            'extra' => $log->extra,
            'created_at' => $log->created_at->toISOString(),
            'updated_at' => $log->updated_at?->toISOString(),
        ]);
    }

    /**
     * Search logs with advanced filtering.
     */
    public function searchLogs(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = LogEntry::query();

        // Apply search filters
        if ($request->has('q')) {
            $searchTerm = $request->get('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'like', "%{$searchTerm}%")
                  ->orWhere('context', 'like', "%{$searchTerm}%")
                  ->orWhere('extra', 'like', "%{$searchTerm}%");
            });
        }

        // Apply other filters
        if ($request->has('level')) {
            $levels = is_array($request->level) ? $request->level : [$request->level];
            $query->whereIn('level', $levels);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
            'links' => [
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ]
        ]);
    }

    /**
     * Get streaming statistics.
     */
    public function stats(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = [
            'stream_stats' => $this->streamService->getStats(),
            'log_counts' => [
                'total' => LogEntry::count(),
                'today' => LogEntry::whereDate('created_at', today())->count(),
                'last_hour' => LogEntry::where('created_at', '>=', now()->subHour())->count(),
                'last_24_hours' => LogEntry::where('created_at', '>=', now()->subDay())->count(),
            ],
            'level_breakdown' => LogEntry::selectRaw('level, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'channel_breakdown' => LogEntry::selectRaw('channel, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('channel')
                ->limit(10)
                ->pluck('count', 'channel')
                ->toArray(),
        ];

        return response()->json($stats);
    }

    /**
     * Get statistics summary.
     */
    public function getStatsSummary(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $days = $request->get('days', 7);
        $fromDate = now()->subDays($days);

        return response()->json([
            'period' => "{$days} days",
            'from_date' => $fromDate->toDateString(),
            'to_date' => now()->toDateString(),
            'summary' => LogEntry::getStats($days),
        ]);
    }

    /**
     * Get statistics trends.
     */
    public function getStatsTrends(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $days = $request->get('days', 7);
        $fromDate = now()->subDays($days);

        $trends = LogEntry::selectRaw('DATE(created_at) as date, level, COUNT(*) as count')
            ->where('created_at', '>=', $fromDate)
            ->groupBy('date', 'level')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dayLogs) {
                return $dayLogs->pluck('count', 'level')->toArray();
            });

        return response()->json([
            'period' => "{$days} days",
            'trends' => $trends,
        ]);
    }

    /**
     * Health check endpoint.
     */
    public function health(Request $request)
    {
        $health = [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'services' => [
                'database' => $this->checkDatabase(),
                'sse' => config('log-management.sse.enabled', true),
                'notifications' => config('log-management.notifications.enabled', true),
            ]
        ];

        $overallHealth = collect($health['services'])->every(fn($status) => $status === true || $status === 'ok');

        return response()->json($health, $overallHealth ? 200 : 503);
    }

    /**
     * Setup the SSE stream.
     */
    protected function setupStream(): void
    {
        // Disable time limit for long-running connection
        set_time_limit(0);

        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Send initial connection message
        echo "data: " . json_encode([
            'type' => 'connected',
            'timestamp' => now()->toISOString(),
            'message' => 'Log stream connected'
        ]) . "\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Send recent log entries to newly connected clients.
     */
    protected function sendRecentLogs(array $filters): void
    {
        $query = LogEntry::query()
            ->orderBy('created_at', 'desc')
            ->limit(50);

        // Apply filters
        if (isset($filters['level'])) {
            $query->whereIn('level', (array) $filters['level']);
        }

        if (isset($filters['channel'])) {
            $query->where('channel', $filters['channel']);
        }

        $logs = $query->get();

        foreach ($logs->reverse() as $log) {
            $logData = [
                'datetime' => $log->created_at->toISOString(),
                'level' => $log->level,
                'message' => $log->message,
                'channel' => $log->channel,
                'context' => json_decode($log->context, true) ?? [],
            ];

            echo $this->streamService->sendToStream($logData);

            if (ob_get_level()) {
                ob_flush();
            }
            flush();
        }
    }

    /**
     * Maintain the SSE connection.
     */
    protected function maintainConnection(): void
    {
        $lastHeartbeat = time();

        while (true) {
            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }

            // Send heartbeat every 30 seconds
            if (time() - $lastHeartbeat > 30) {
                echo $this->streamService->sendHeartbeat();
                $lastHeartbeat = time();

                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }

            // Small delay to prevent excessive CPU usage
            usleep(100000); // 0.1 seconds

            // Break after 10 minutes to prevent memory leaks
            if (time() - $_SERVER['REQUEST_TIME'] > 600) {
                echo "data: " . json_encode([
                    'type' => 'timeout',
                    'message' => 'Connection timeout, please reconnect'
                ]) . "\n\n";
                break;
            }
        }
    }

    /**
     * Get filters from request parameters.
     */
    protected function getFiltersFromRequest(Request $request): array
    {
        $filters = [];

        if ($request->has('level')) {
            $filters['level'] = is_array($request->level) ? $request->level : [$request->level];
        }

        if ($request->has('channel')) {
            $filters['channel'] = $request->channel;
        }

        if ($request->has('search')) {
            $filters['search'] = $request->search;
        }

        return $filters;
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
            $requiredPermission = config('log-management.auth.permission', 'view-logs');

            // If no specific permission method exists, allow authenticated users
            if (!method_exists($user, 'can')) {
                return true;
            }

            return $user->can($requiredPermission);
        }

        return false;
    }

    /**
     * Check database connectivity.
     */
    protected function checkDatabase(): string
    {
        try {
            LogEntry::count();
            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }
}