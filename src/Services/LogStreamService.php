<?php

namespace Fulgid\LogManagement\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LogStreamService
{
    protected array $connections = [];
    protected array $filters = [];

    /**
     * Create a new SSE response.
     */
    public function createStream(array $filters = []): Response
    {
        $response = new Response();
        
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control, Authorization, X-Log-Management-Key');

        return $response;
    }

    /**
     * Send log data to SSE stream with proper formatting.
     */
    public function sendLogToStream(array $logData): string
    {
        // Apply filters
        if (!$this->shouldStreamLog($logData)) {
            return '';
        }

        $eventData = [
            'type' => 'log',
            'id' => $logData['id'] ?? uniqid('log_'),
            'timestamp' => $logData['datetime'] ?? now()->toISOString(),
            'level' => $logData['level'],
            'message' => $logData['message'],
            'channel' => $logData['channel'] ?? 'default',
            'context' => $logData['context'] ?? [],
            'environment' => $logData['environment'] ?? app()->environment(),
            'user_id' => $logData['user_id'] ?? null,
            'url' => $logData['url'] ?? null,
            'ip_address' => $logData['ip_address'] ?? null,
            'execution_time' => $logData['execution_time'] ?? null,
            'memory_usage' => $logData['memory_usage'] ?? null,
        ];

        return $this->formatSSEMessage($eventData, 'log');
    }

    /**
     * Send a heartbeat to keep the connection alive.
     */
    public function sendHeartbeat(): string
    {
        $heartbeatData = [
            'type' => 'heartbeat',
            'timestamp' => now()->toISOString(),
            'uptime' => $this->getUptime(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        return $this->formatSSEMessage($heartbeatData, 'heartbeat');
    }

    /**
     * Send connection status message.
     */
    public function sendConnectionStatus(string $status, array $data = []): string
    {
        $statusData = array_merge([
            'type' => 'connection_status',
            'status' => $status,
            'timestamp' => now()->toISOString(),
        ], $data);

        return $this->formatSSEMessage($statusData, 'status');
    }

    /**
     * Send error message.
     */
    public function sendError(string $message, array $context = []): string
    {
        $errorData = [
            'type' => 'error',
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        return $this->formatSSEMessage($errorData, 'error');
    }

    /**
     * Send statistics update.
     */
    public function sendStatistics(array $stats): string
    {
        $statsData = array_merge([
            'type' => 'statistics',
            'timestamp' => now()->toISOString(),
        ], $stats);

        return $this->formatSSEMessage($statsData, 'statistics');
    }

    /**
     * Format data as proper SSE message.
     */
    protected function formatSSEMessage(array $data, string $event = 'message', ?string $id = null): string
    {
        $output = '';
        
        if ($id) {
            $output .= "id: {$id}\n";
        }
        
        $output .= "event: {$event}\n";
        $output .= "data: " . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";
        
        return $output;
    }

    /**
     * Add a filter for streaming.
     */
    public function addFilter(callable $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * Remove all filters.
     */
    public function clearFilters(): void
    {
        $this->filters = [];
    }

    /**
     * Check if log should be streamed based on filters.
     */
    protected function shouldStreamLog(array $logData): bool
    {
        // Check if SSE is enabled
        if (!config('log-management.sse.enabled', true)) {
            return false;
        }

        // Apply custom filters
        foreach ($this->filters as $filter) {
            if (!$filter($logData)) {
                return false;
            }
        }

        // Check minimum log level for streaming
        $streamLevels = config('log-management.sse.levels', [
            'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        ]);
        
        return in_array(strtolower($logData['level']), array_map('strtolower', $streamLevels));
    }

    /**
     * Get streaming statistics.
     */
    public function getStats(): array
    {
        return [
            'active_connections' => $this->getActiveConnectionCount(),
            'total_filters' => count($this->filters),
            'sse_enabled' => config('log-management.sse.enabled', true),
            'uptime' => $this->getUptime(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }

    /**
     * Get active connection count.
     */
    protected function getActiveConnectionCount(): int
    {
        // In a real implementation, you'd track this in Redis or database
        // For now, return cached count or estimate
        return Cache::get('sse_active_connections', 0);
    }

    /**
     * Get uptime in seconds.
     */
    protected function getUptime(): int
    {
        // Calculate uptime since application start
        if (defined('LARAVEL_START')) {
            return (int) (microtime(true) - LARAVEL_START);
        }
        
        return 0;
    }

    /**
     * Register a new connection.
     */
    public function registerConnection(string $connectionId, array $filters = []): void
    {
        $this->connections[$connectionId] = [
            'id' => $connectionId,
            'filters' => $filters,
            'connected_at' => now(),
            'last_activity' => now(),
        ];

        // Update cached connection count
        Cache::put('sse_active_connections', count($this->connections), 300);
    }

    /**
     * Unregister a connection.
     */
    public function unregisterConnection(string $connectionId): void
    {
        unset($this->connections[$connectionId]);
        
        // Update cached connection count
        Cache::put('sse_active_connections', count($this->connections), 300);
    }

    /**
     * Clean up stale connections.
     */
    public function cleanupStaleConnections(): void
    {
        $timeout = config('log-management.sse.connection_timeout', 600);
        $cutoff = now()->subSeconds($timeout);
        
        foreach ($this->connections as $id => $connection) {
            if ($connection['last_activity']->lt($cutoff)) {
                $this->unregisterConnection($id);
            }
        }
    }

    /**
     * Update connection activity.
     */
    public function updateConnectionActivity(string $connectionId): void
    {
        if (isset($this->connections[$connectionId])) {
            $this->connections[$connectionId]['last_activity'] = now();
        }
    }

    /**
     * Get connection info.
     */
    public function getConnectionInfo(string $connectionId): ?array
    {
        return $this->connections[$connectionId] ?? null;
    }

    /**
     * Get all active connections.
     */
    public function getActiveConnections(): array
    {
        return $this->connections;
    }
}