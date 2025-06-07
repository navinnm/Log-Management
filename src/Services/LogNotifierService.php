<?php

namespace Fulgid\LogManagement\Services;

use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Fulgid\LogManagement\Models\NotificationSetting;
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

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($this->channels as $name => $channel) {
            if ($channel->isEnabled()) {
                try {
                    // Get the notification setting for this channel
                    $setting = NotificationSetting::forChannel($name)->first();
                    
                    // Check if we should notify based on conditions
                    if ($setting && !$setting->shouldNotify($logData)) {
                        continue;
                    }

                    $success = $channel->send($logData);
                    
                    if ($success) {
                        $totalSent++;
                        $this->stats['notifications_sent']++;
                        $this->stats['last_notification'] = now()->toISOString();
                        
                        // Update notification setting statistics
                        if ($setting) {
                            $setting->increment('notification_count');
                            $setting->update(['last_notification_at' => now()]);
                        }
                        
                        // Log success for debugging
                        Log::channel('single')->info("Notification sent successfully via {$name}", [
                            'message' => $message,
                            'level' => $level,
                        ]);
                    } else {
                        $totalFailed++;
                        $this->stats['notifications_failed']++;
                        
                        // Update failure statistics
                        if ($setting) {
                            $setting->increment('failure_count');
                            $setting->update(['last_failure_at' => now()]);
                        }
                        
                        // Log failure for debugging
                        Log::channel('single')->warning("Notification failed via {$name}", [
                            'message' => $message,
                            'level' => $level,
                        ]);
                    }
                } catch (\Exception $e) {
                    $totalFailed++;
                    $this->stats['notifications_failed']++;
                    
                    // Update failure statistics
                    $setting = NotificationSetting::forChannel($name)->first();
                    if ($setting) {
                        $setting->increment('failure_count');
                        $setting->update([
                            'last_failure_at' => now(),
                            'last_error' => $e->getMessage(),
                        ]);
                    }
                    
                    // Log the error but don't create a notification loop
                    Log::channel('single')->error("Failed to send notification via {$name}: " . $e->getMessage(), [
                        'exception' => $e->getTraceAsString(),
                        'message' => $message,
                        'level' => $level,
                    ]);
                }
            }
        }

        // Store summary statistics if any notifications were processed
        if ($totalSent > 0 || $totalFailed > 0) {
            $this->storeNotificationStats($totalSent, $totalFailed, $level);
        }
    }

    /**
     * Store notification statistics.
     */
    protected function storeNotificationStats(int $sent, int $failed, string $level): void
    {
        try {
            // Check if log_management_stats table exists
            if (\Schema::hasTable('log_management_stats')) {
                \DB::table('log_management_stats')->updateOrInsert(
                    [
                        'date' => now()->toDateString(),
                        'metric_type' => 'daily_summary',
                        'metric_name' => 'notifications_sent',
                        'dimension_key' => 'level',
                        'dimension_value' => $level,
                    ],
                    [
                        'count' => \DB::raw("count + {$sent}"),
                        'updated_at' => now(),
                    ]
                );

                if ($failed > 0) {
                    \DB::table('log_management_stats')->updateOrInsert(
                        [
                            'date' => now()->toDateString(),
                            'metric_type' => 'daily_summary',
                            'metric_name' => 'notifications_failed',
                            'dimension_key' => 'level',
                            'dimension_value' => $level,
                        ],
                        [
                            'count' => \DB::raw("count + {$failed}"),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::channel('single')->error('Failed to store notification stats: ' . $e->getMessage());
        }
    }

    /**
     * Add a notification channel.
     */
    public function addChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
        
        // Ensure notification setting exists for this channel
        $this->ensureNotificationSetting($name);
    }

    /**
     * Ensure notification setting exists for channel.
     */
    protected function ensureNotificationSetting(string $channelName): void
    {
        try {
            NotificationSetting::firstOrCreate(
                ['channel' => strtolower($channelName)],
                [
                    'enabled' => config("log-management.notifications.channels.{$channelName}.enabled", false),
                    'settings' => NotificationSetting::getDefaultSettings($channelName),
                    'conditions' => [
                        'levels' => config('log-management.notifications.levels', ['error', 'critical', 'emergency']),
                        'environments' => config('log-management.environments', ['production']),
                    ],
                ]
            );
        } catch (\Exception $e) {
            Log::channel('single')->error("Failed to ensure notification setting for {$channelName}: " . $e->getMessage());
        }
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
        // Get fresh stats from database
        $dbStats = $this->getStatsFromDatabase();
        
        return array_merge($this->stats, $dbStats);
    }

    /**
     * Get statistics from database.
     */
    protected function getStatsFromDatabase(): array
    {
        try {
            $stats = [
                'notifications_sent' => 0,
                'notifications_failed' => 0,
                'last_notification' => null,
                'channels' => [],
            ];

            // Get total notification count from notification settings
            $channelStats = NotificationSetting::select('channel', 'notification_count', 'failure_count', 'last_notification_at')
                ->get();

            foreach ($channelStats as $channelStat) {
                $stats['notifications_sent'] += $channelStat->notification_count;
                $stats['notifications_failed'] += $channelStat->failure_count;
                
                if ($channelStat->last_notification_at && 
                    (!$stats['last_notification'] || $channelStat->last_notification_at > $stats['last_notification'])) {
                    $stats['last_notification'] = $channelStat->last_notification_at->toISOString();
                }

                $stats['channels'][$channelStat->channel] = [
                    'sent' => $channelStat->notification_count,
                    'failed' => $channelStat->failure_count,
                    'last_notification' => $channelStat->last_notification_at?->toISOString(),
                ];
            }

            return $stats;
        } catch (\Exception $e) {
            Log::channel('single')->error('Failed to get stats from database: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if the service is enabled.
     */
    public function isEnabled(): bool
    {
        return config('log-management.enabled', true) && 
               config('log-management.notifications.enabled', true);
    }

    /**
     * Reset statistics (for testing).
     */
    public function resetStats(): void
    {
        $this->stats = [
            'notifications_sent' => 0,
            'notifications_failed' => 0,
            'last_notification' => null,
        ];

        // Also reset database stats if needed
        NotificationSetting::query()->update([
            'notification_count' => 0,
            'failure_count' => 0,
            'last_notification_at' => null,
            'last_failure_at' => null,
        ]);
    }
}