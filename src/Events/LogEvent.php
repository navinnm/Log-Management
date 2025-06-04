<?php

namespace Fulgid\LogManagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LogEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $logData;
    public string $eventId;
    public string $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(array $logData)
    {
        $this->logData = $logData;
        $this->eventId = uniqid('log_', true);
        $this->timestamp = now()->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // General log channel
        $channels[] = new Channel('log-management.logs');

        // Level-specific channel
        $channels[] = new Channel('log-management.logs.' . strtolower($this->logData['level']));

        // Channel-specific if available
        if (isset($this->logData['channel'])) {
            $channels[] = new Channel('log-management.logs.channel.' . $this->logData['channel']);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->eventId,
            'timestamp' => $this->timestamp,
            'level' => $this->logData['level'],
            'message' => $this->logData['message'],
            'channel' => $this->logData['channel'] ?? 'default',
            'context' => $this->logData['context'] ?? [],
            'extra' => $this->logData['extra'] ?? [],
            'environment' => app()->environment(),
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
        // Check if broadcasting is enabled
        if (!config('log-management.sse.enabled', true)) {
            return false;
        }

        // Check if this log level should be broadcasted
        $broadcastLevels = config('log-management.sse.levels', [
            'debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'
        ]);

        return in_array(strtolower($this->logData['level']), $broadcastLevels);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'log-management',
            'log-level:' . strtolower($this->logData['level']),
            'log-channel:' . ($this->logData['channel'] ?? 'default'),
        ];
    }
}