<?php

namespace Fulgid\LogManagement\Services;

use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;

class LogNotifierService
{
    protected array $channels = [];
    protected array $stats = [
        'notifications_sent' => 0,
        'notifications_failed' => 0,
        'last_notification' => null,
    ];

    /**
     * Send notification through all enabled channels.
     */
    public function notify(string $message, string $level = 'error', array $context = []): void
    {
        $logData = [
            'message' => $message,
            'level' => $level,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'url' => request()->fullUrl() ?? 'N/A',
            'user_agent' => request()->userAgent() ?? 'N/A',
            'ip' => request()->ip() ?? 'N/A',
        ];

        foreach ($this->channels as $name => $channel) {
            if ($channel->isEnabled()) {
                try {
                    $success = $channel->send($logData);
                    
                    if ($success) {
                        $this->stats['notifications_sent']++;
                        $this->stats['last_notification'] = now()->toISOString();
                    } else {
                        $this->stats['notifications_failed']++;
                    }
                } catch (\Exception $e) {
                    $this->stats['notifications_failed']++;
                    
                    // Log the error but don't create a notification loop
                    Log::channel('single')->error("Failed to send notification via {$name}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Add a notification channel.
     */
    public function addChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    /**
     * Remove a notification channel.
     */
    public function removeChannel(string $name): void
    {
        unset($this->channels[$name]);
    }

    /**
     * Get all notification channels.
     */
    public function getChannels(): array
    {
        return $this->channels;
    }

    /**
     * Get notification statistics.
     */
    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Check if the service is enabled.
     */
    public function isEnabled(): bool
    {
        return config('log-management.enabled', true) && 
               config('log-management.notifications.enabled', true);
    }
}