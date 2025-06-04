<?php

namespace Fulgid\LogManagement\Services;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control');

        return $response;
    }

    /**
     * Send log data to SSE stream.
     */
    public function sendToStream(array $logData): string
    {
        // Apply filters
        if (!$this->shouldStreamLog($logData)) {
            return '';
        }

        $eventData = json_encode([
            'id' => uniqid(),
            'timestamp' => $logData['datetime'] ?? now()->toISOString(),
            'level' => $logData['level'],
            'message' => $logData['message'],
            'channel' => $logData['channel'] ?? 'default',
            'context' => $logData['context'] ?? [],
        ]);

        return "data: {$eventData}\n\n";
    }

    /**
     * Send a heartbeat to keep the connection alive.
     */
    public function sendHeartbeat(): string
    {
        return "data: " . json_encode(['type' => 'heartbeat', 'timestamp' => now()->toISOString()]) . "\n\n";
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
        $streamLevels = config('log-management.sse.levels', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency']);
        
        return in_array(strtolower($logData['level']), $streamLevels);
    }

    /**
     * Get streaming statistics.
     */
    public function getStats(): array
    {
        return [
            'active_connections' => count($this->connections),
            'total_filters' => count($this->filters),
            'sse_enabled' => config('log-management.sse.enabled', true),
        ];
    }
}