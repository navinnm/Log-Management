<?php

namespace Fulgid\LogManagement\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fulgid\LogManagement\Models\LogEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;

class LogStreamController extends Controller
{
    /**
     * Stream logs via Server-Sent Events - MINIMAL VERSION FOR TESTING
     */
    public function stream(Request $request): StreamedResponse
    {
        // Log the incoming request for debugging
        Log::info('SSE Stream request received', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'query' => $request->query->all()
        ]);

        // Simple access check
        if (!$this->hasAccess($request)) {
            Log::warning('SSE access denied');
            abort(403, 'Unauthorized access to log stream');
        }

        return new StreamedResponse(function () use ($request) {
            $this->handleStream($request);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control, Authorization, X-Log-Management-Key',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Handle the SSE stream - SIMPLIFIED VERSION
     */
    protected function handleStream(Request $request): void
    {
        // Disable time limit
        set_time_limit(0);
        
        // Disable all output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        try {
            Log::info('SSE Stream starting');

            // Send immediate connection message
            $this->sendSSE([
                'type' => 'connected',
                'message' => 'Stream connected successfully',
                'timestamp' => now()->toISOString(),
                'server_time' => time(),
            ]);

            // Send recent logs if requested
            if ($request->boolean('include_recent')) {
                $this->sendRecentLogs();
            }

            // Send a few test messages immediately
            for ($i = 1; $i <= 3; $i++) {
                $this->sendSSE([
                    'type' => 'test',
                    'message' => "Test message {$i}",
                    'timestamp' => now()->toISOString(),
                    'number' => $i
                ]);
                
                $this->flushOutput();
                sleep(1); // 1 second delay between test messages
            }

            // Start main loop with shorter timeout for testing
            $this->mainLoop($request);

        } catch (\Exception $e) {
            Log::error('SSE Stream error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendSSE([
                'type' => 'error',
                'message' => 'Stream error: ' . $e->getMessage(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Main stream loop - SIMPLIFIED
     */
    protected function mainLoop(Request $request): void
    {
        $startTime = time();
        $lastHeartbeat = time();
        $lastLogId = LogEntry::max('id') ?? 0;
        $maxDuration = 60; // 1 minute for testing
        
        Log::info('SSE Main loop starting', [
            'start_time' => $startTime,
            'last_log_id' => $lastLogId,
            'max_duration' => $maxDuration
        ]);

        while (!connection_aborted() && (time() - $startTime) < $maxDuration) {
            $currentTime = time();
            
            // Send heartbeat every 10 seconds
            if ($currentTime - $lastHeartbeat >= 10) {
                $this->sendSSE([
                    'type' => 'heartbeat',
                    'timestamp' => now()->toISOString(),
                    'uptime' => $currentTime - $startTime,
                    'memory' => memory_get_usage(true),
                ]);
                
                $lastHeartbeat = $currentTime;
                Log::debug('SSE Heartbeat sent');
            }

            // Check for new logs
            $newMaxId = $this->checkNewLogs($lastLogId);
            if ($newMaxId > $lastLogId) {
                $lastLogId = $newMaxId;
            }

            // Check if client disconnected
            if (connection_aborted()) {
                Log::info('SSE Client disconnected');
                break;
            }

            // Small sleep to prevent excessive CPU usage
            usleep(500000); // 0.5 seconds
        }

        // Send closing message
        $this->sendSSE([
            'type' => 'closing',
            'reason' => connection_aborted() ? 'client_disconnected' : 'timeout',
            'duration' => time() - $startTime,
            'timestamp' => now()->toISOString()
        ]);

        Log::info('SSE Stream ended', [
            'duration' => time() - $startTime,
            'reason' => connection_aborted() ? 'client_disconnected' : 'timeout'
        ]);
    }

    /**
     * Send recent logs
     */
    protected function sendRecentLogs(): void
    {
        try {
            $logs = LogEntry::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $this->sendSSE([
                'type' => 'recent_logs_info',
                'count' => $logs->count(),
                'timestamp' => now()->toISOString()
            ]);

            foreach ($logs->reverse() as $log) {
                $this->sendSSE([
                    'type' => 'log',
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'channel' => $log->channel,
                    'timestamp' => $log->created_at->toISOString(),
                    'context' => $log->context ?? [],
                ]);
                
                $this->flushOutput();
                usleep(100000); // 0.1 second delay
            }

            Log::info('SSE Recent logs sent', ['count' => $logs->count()]);

        } catch (\Exception $e) {
            Log::error('SSE Failed to send recent logs', ['error' => $e->getMessage()]);
            
            $this->sendSSE([
                'type' => 'error',
                'message' => 'Failed to load recent logs',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Check for new logs
     */
    protected function checkNewLogs(int $lastLogId): int
    {
        try {
            $newLogs = LogEntry::where('id', '>', $lastLogId)
                ->orderBy('id', 'asc')
                ->limit(10)
                ->get();

            $maxId = $lastLogId;

            foreach ($newLogs as $log) {
                $this->sendSSE([
                    'type' => 'log',
                    'id' => $log->id,
                    'level' => $log->level,
                    'message' => $log->message,
                    'channel' => $log->channel,
                    'timestamp' => $log->created_at->toISOString(),
                    'context' => $log->context ?? [],
                    'is_new' => true,
                ]);
                
                $maxId = max($maxId, $log->id);
                $this->flushOutput();
            }

            if ($newLogs->count() > 0) {
                Log::info('SSE New logs sent', ['count' => $newLogs->count()]);
            }

            return $maxId;

        } catch (\Exception $e) {
            Log::error('SSE Error checking new logs', ['error' => $e->getMessage()]);
            return $lastLogId;
        }
    }

    /**
     * Send SSE message with proper formatting
     */
    protected function sendSSE(array $data, ?string $id = null): void
    {
        if ($id) {
            echo "id: {$id}\n";
        }
        
        echo "data: " . json_encode($data, JSON_UNESCAPED_SLASHES) . "\n\n";
        $this->flushOutput();
    }

    /**
     * Flush output to client
     */
    protected function flushOutput(): void
    {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Simple access check
     */
    protected function hasAccess(Request $request): bool
    {
        // If auth is disabled, allow access
        if (!config('log-management.auth.enabled', false)) {
            Log::info('SSE Auth disabled, allowing access');
            return true;
        }

        // Check API key
        $apiKey = $request->header('X-Log-Management-Key') ?? 
                  $request->header('Authorization') ?? 
                  $request->query('key');

        if ($apiKey && str_starts_with($apiKey, 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        $validKeys = array_filter(config('log-management.auth.api_keys', []));

        if ($apiKey && in_array($apiKey, $validKeys)) {
            Log::info('SSE Valid API key provided');
            return true;
        }

        Log::warning('SSE Invalid or missing API key', [
            'provided_key' => $apiKey ? substr($apiKey, 0, 10) . '...' : 'none',
            'valid_keys_count' => count($validKeys)
        ]);

        return false;
    }

    // Keep your other methods (getLogs, getLog, etc.) unchanged...
    
    /**
     * Get log entries for initial load or pagination.
     */
    public function getLogs(Request $request)
    {
        if (!$this->hasAccess($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = LogEntry::query();

        // Apply filters (simplified)
        if ($request->has('level')) {
            $levels = is_array($request->level) ? $request->level : [$request->level];
            $query->whereIn('level', $levels);
        }

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
            ]
        ];

        return response()->json($health, 200);
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