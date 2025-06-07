<?php

namespace Fulgid\LogManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Models\LogEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;

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

        // Check if SSE is enabled
        if (!config('log-management.sse.enabled', true)) {
            return response('SSE is disabled', 503);
        }

        return new StreamedResponse(function () use ($request) {
            $this->setupSSEStream($request);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control, Authorization, X-Log-Management-Key',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * Setup the SSE stream with proper formatting.
     */
    protected function setupSSEStream(Request $request): void
    {
        // Configure PHP for long-running processes
        set_time_limit(0);
        ignore_user_abort(false);
        
        // Disable output buffering completely
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Start output buffering with immediate flush
        ob_start(null, 0, PHP_OUTPUT_HANDLER_FLUSHABLE);

        try {
            // Send initial connection message
            $this->sendSSEMessage([
                'type' => 'connected',
                'message' => 'Log stream connected successfully',
                'timestamp' => now()->toISOString(),
                'filters' => $request->all(),
                'config' => [
                    'heartbeat_interval' => 30,
                    'timeout' => 1800, // 30 minutes
                ]
            ], 'connection');

            // Send recent logs if requested
            if ($request->boolean('include_recent')) {
                $this->sendRecentLogs($request);
            }

            // Start the main connection loop
            $this->maintainConnection($request);

        } catch (\Exception $e) {
            Log::error('SSE Stream error: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            $this->sendSSEMessage([
                'type' => 'error',
                'message' => 'Stream error occurred',
                'timestamp' => now()->toISOString()
            ], 'error');
        }
    }

    /**
     * Send a properly formatted SSE message.
     */
    protected function sendSSEMessage(array $data, string $event = 'message', ?string $id = null): void
    {
        if ($id) {
            echo "id: {$id}\n";
        }
        
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
        
        $this->flushOutput();
    }

    /**
     * Send recent logs to initialize the stream.
     */
    protected function sendRecentLogs(Request $request): void
    {
        try {
            $query = LogEntry::orderBy('created_at', 'desc')->limit(50);
            
            // Apply filters
            $this->applyFilters($query, $request);
            
            $logs = $query->get();
            
            foreach ($logs->reverse() as $log) {
                $this->sendSSEMessage([
                    'type' => 'log',
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'channel' => $log->channel,
                    'timestamp' => $log->created_at->toISOString(),
                    'context' => $log->context ?? [],
                    'user_id' => $log->user_id,
                    'environment' => $log->environment,
                    'url' => $log->url,
                    'ip_address' => $log->ip_address,
                    'execution_time' => $log->execution_time,
                    'memory_usage' => $log->memory_usage,
                ], 'log', 'log-' . $log->id);
                
                // Small delay to prevent overwhelming
                usleep(50000); // 0.05 seconds
            }
            
            $this->sendSSEMessage([
                'type' => 'recent_logs_complete',
                'count' => $logs->count(),
                'timestamp' => now()->toISOString()
            ], 'status');
            
        } catch (\Exception $e) {
            Log::error('Failed to send recent logs: ' . $e->getMessage());
            
            $this->sendSSEMessage([
                'type' => 'error',
                'message' => 'Failed to load recent logs',
                'timestamp' => now()->toISOString()
            ], 'error');
        }
    }

    /**
     * Maintain the SSE connection with proper event handling.
     */
    protected function maintainConnection(Request $request): void
    {
        $lastHeartbeat = time();
        $lastLogId = $this->getLastLogId();
        $startTime = time();
        $heartbeatInterval = 30; // seconds
        $maxDuration = 1800; // 30 minutes
        
        while (!connection_aborted() && (time() - $startTime) < $maxDuration) {
            $currentTime = time();
            
            // Send heartbeat
            if ($currentTime - $lastHeartbeat >= $heartbeatInterval) {
                $this->sendSSEMessage([
                    'type' => 'heartbeat',
                    'timestamp' => now()->toISOString(),
                    'uptime' => $currentTime - $startTime,
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true),
                    'connection_count' => $this->getActiveConnectionCount(),
                ], 'heartbeat');
                
                $lastHeartbeat = $currentTime;
            }

            // Check for new logs
            $newLogId = $this->checkForNewLogs($request, $lastLogId);
            if ($newLogId > $lastLogId) {
                $lastLogId = $newLogId;
            }

            // Send statistics periodically (every 2 minutes)
            if ($currentTime % 120 === 0) {
                $this->sendStatistics();
            }

            // Sleep to prevent excessive CPU usage
            usleep(500000); // 0.5 seconds
        }

        // Send connection closing message
        $this->sendSSEMessage([
            'type' => 'connection_closing',
            'reason' => connection_aborted() ? 'client_disconnected' : 'timeout',
            'duration' => time() - $startTime,
            'timestamp' => now()->toISOString()
        ], 'disconnect');
    }

    /**
     * Check for new log entries and send them.
     */
    protected function checkForNewLogs(Request $request, int $lastLogId): int
    {
        try {
            $query = LogEntry::where('id', '>', $lastLogId)
                ->orderBy('id', 'asc')
                ->limit(20);
            
            // Apply filters
            $this->applyFilters($query, $request);
            
            $newLogs = $query->get();
            $maxId = $lastLogId;

            foreach ($newLogs as $log) {
                $this->sendSSEMessage([
                    'type' => 'log',
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'channel' => $log->channel,
                    'timestamp' => $log->created_at->toISOString(),
                    'context' => $log->context ?? [],
                    'user_id' => $log->user_id,
                    'environment' => $log->environment,
                    'url' => $log->url,
                    'ip_address' => $log->ip_address,
                    'execution_time' => $log->execution_time,
                    'memory_usage' => $log->memory_usage,
                    'is_new' => true,
                ], 'log', 'log-' . $log->id);
                
                $maxId = max($maxId, $log->id);
            }

            return $maxId;
            
        } catch (\Exception $e) {
            Log::error('Error checking for new logs: ' . $e->getMessage());
            return $lastLogId;
        }
    }

    /**
     * Apply filters to the log query.
     */
    protected function applyFilters($query, Request $request): void
    {
        // Filter by log levels
        if ($request->has('level') && !empty($request->level)) {
            $levels = is_array($request->level) ? $request->level : [$request->level];
            $query->whereIn('level', $levels);
        }

        // Filter by channels
        if ($request->has('channel') && !empty($request->channel)) {
            $query->where('channel', $request->channel);
        }

        // Filter by search term
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('context', 'like', "%{$search}%");
            });
        }

        // Filter by user ID
        if ($request->has('user_id') && !empty($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by environment
        if ($request->has('environment') && !empty($request->environment)) {
            $query->where('environment', $request->environment);
        }

        // Filter by date range
        if ($request->has('since')) {
            $since = $request->since;
            if (is_numeric($since)) {
                // Unix timestamp
                $query->where('created_at', '>=', date('Y-m-d H:i:s', $since));
            } else {
                // Relative time (e.g., '1h', '30m', '1d')
                $query->where('created_at', '>=', $this->parseRelativeTime($since));
            }
        }
    }

    /**
     * Parse relative time strings like '1h', '30m', '1d'.
     */
    protected function parseRelativeTime(string $time): string
    {
        if (preg_match('/^(\d+)([smhd])$/', $time, $matches)) {
            $amount = (int) $matches[1];
            $unit = $matches[2];
            
            return match ($unit) {
                's' => now()->subSeconds($amount)->toDateTimeString(),
                'm' => now()->subMinutes($amount)->toDateTimeString(),
                'h' => now()->subHours($amount)->toDateTimeString(),
                'd' => now()->subDays($amount)->toDateTimeString(),
                default => now()->subHour()->toDateTimeString(),
            };
        }
        
        return now()->subHour()->toDateTimeString();
    }

    /**
     * Get the last log ID for tracking new entries.
     */
    protected function getLastLogId(): int
    {
        try {
            return LogEntry::max('id') ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get active connection count (mock implementation).
     */
    protected function getActiveConnectionCount(): int
    {
        // This would need to be implemented with a shared store like Redis
        // For now, return a placeholder
        return 1;
    }

    /**
     * Send current statistics.
     */
    protected function sendStatistics(): void
    {
        try {
            $stats = [
                'type' => 'statistics',
                'timestamp' => now()->toISOString(),
                'total_logs' => LogEntry::count(),
                'logs_today' => LogEntry::whereDate('created_at', today())->count(),
                'errors_today' => LogEntry::whereDate('created_at', today())
                    ->whereIn('level', ['error', 'critical', 'emergency', 'alert'])
                    ->count(),
                'level_breakdown' => LogEntry::selectRaw('level, COUNT(*) as count')
                    ->whereDate('created_at', today())
                    ->groupBy('level')
                    ->pluck('count', 'level')
                    ->toArray(),
            ];
            
            $this->sendSSEMessage($stats, 'statistics');
            
        } catch (\Exception $e) {
            Log::error('Failed to send statistics: ' . $e->getMessage());
        }
    }

    /**
     * Flush output to client.
     */
    protected function flushOutput(): void
    {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    // ... [keep all your existing methods: getLogs, getLog, searchLogs, etc.]

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
        $apiKey = $request->header('X-Log-Management-Key') ?? 
                  $request->header('Authorization') ?? 
                  $request->query('key');

        // Handle Bearer token format
        if ($apiKey && str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        $validKeys = array_filter(config('log-management.auth.api_keys', []));

        if ($apiKey && in_array($apiKey, $validKeys)) {
            return true;
        }

        // Check for authenticated user with proper permissions
        if (auth()->check()) {
            $user = auth()->user();
            $requiredPermission = config('log-management.auth.permission', 'view-logs');

            if (!method_exists($user, 'can')) {
                return true;
            }

            return $user->can($requiredPermission);
        }

        return false;
    }
}