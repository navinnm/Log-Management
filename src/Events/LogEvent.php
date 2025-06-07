<?php

namespace Fulgid\LogManagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $logData;

    /**
     * Create a new event instance.
     */
    public function __construct(array $logData)
    {
        $this->logData = $logData;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('log-management'),
            new PrivateChannel('log-management.level.' . strtolower($this->logData['level'])),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => uniqid(),
            'type' => 'log',
            'timestamp' => $this->logData['datetime'] ?? now()->toISOString(),
            'level' => $this->logData['level'],
            'message' => $this->logData['message'],
            'channel' => $this->logData['channel'] ?? 'default',
            'environment' => $this->logData['environment'],
            'context' => $this->logData['context'] ?? [],
            'user_id' => $this->logData['user_id'] ?? null,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'log.created';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if SSE is enabled and this is an appropriate log level
        if (!config('log-management.sse.enabled', true)) {
            return false;
        }

        $streamLevels = config('log-management.sse.levels', [
            'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info'
        ]);

        return in_array(strtolower($this->logData['level']), $streamLevels);
    }
}