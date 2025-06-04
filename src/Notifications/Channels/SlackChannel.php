<?php

namespace Fulgid\LogManagement\Notifications\Channels;

use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackChannel implements NotificationChannelInterface
{
    protected string $name = 'slack';

    /**
     * Send a notification through Slack.
     */
    public function send(array $logData): bool
    {
        try {
            $setting = NotificationSetting::forChannel($this->name)->first();
            
            if (!$setting || !$setting->shouldNotify($logData)) {
                return false;
            }

            $webhookUrl = $setting->getSetting('webhook_url') ?? 
                         config('log-management.notifications.channels.slack.webhook_url');

            if (!$webhookUrl) {
                Log::channel('single')->warning('Slack notification skipped: No webhook URL configured');
                return false;
            }

            $payload = $this->buildSlackPayload($logData, $setting);
            
            $response = Http::timeout(10)->post($webhookUrl, $payload);

            if ($response->successful()) {
                $setting->markAsNotified();
                return true;
            }

            Log::channel('single')->error('Slack notification failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::channel('single')->error('Failed to send Slack notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if this notification channel is enabled.
     */
    public function isEnabled(): bool
    {
        if (!config('log-management.notifications.channels.slack.enabled', false)) {
            return false;
        }

        $setting = NotificationSetting::forChannel($this->name)->first();
        
        return $setting ? $setting->enabled : true;
    }

    /**
     * Get the channel name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Validate the channel configuration.
     */
    public function validateConfiguration(): bool
    {
        $setting = NotificationSetting::forChannel($this->name)->first();
        $webhookUrl = $setting?->getSetting('webhook_url') ?? 
                     config('log-management.notifications.channels.slack.webhook_url');
        
        return !empty($webhookUrl) && filter_var($webhookUrl, FILTER_VALIDATE_URL);
    }

    /**
     * Get the channel configuration requirements.
     */
    public function getConfigurationRequirements(): array
    {
        return [
            'webhook_url' => 'Slack webhook URL',
            'channel' => 'Slack channel (optional, defaults to #alerts)',
            'username' => 'Bot username (optional)',
            'icon_emoji' => 'Bot emoji icon (optional)',
            'icon_url' => 'Bot icon URL (optional)',
        ];
    }

    /**
     * Test the channel connectivity.
     */
    public function testConnection(): array
    {
        try {
            if (!$this->validateConfiguration()) {
                return [
                    'success' => false,
                    'message' => 'Invalid Slack configuration',
                ];
            }

            $testLogData = [
                'message' => 'Test notification from Log Management Package',
                'level' => 'info',
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'context' => ['test' => true],
            ];

            $result = $this->send($testLogData);

            return [
                'success' => $result,
                'message' => $result ? 'Test Slack message sent successfully' : 'Failed to send test Slack message',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Slack test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build the Slack payload.
     */
    protected function buildSlackPayload(array $logData, ?NotificationSetting $setting = null): array
    {
        $level = strtoupper($logData['level']);
        $environment = $logData['environment'];
        $message = $logData['message'];
        $timestamp = $logData['timestamp'];

        $payload = [
            'text' => $this->getSlackText($logData),
            'channel' => $setting?->getSetting('channel') ?? 
                        config('log-management.notifications.channels.slack.channel', '#alerts'),
            'username' => $setting?->getSetting('username') ?? 
                         config('log-management.notifications.channels.slack.username', 'Log Management'),
            'attachments' => [
                [
                    'color' => $this->getSlackColor($logData['level']),
                    'title' => "Log Alert: {$level} in {$environment}",
                    'text' => strlen($message) > 300 ? substr($message, 0, 300) . '...' : $message,
                    'fields' => $this->buildSlackFields($logData),
                    'footer' => 'Log Management Package',
                    'ts' => strtotime($timestamp),
                ]
            ]
        ];

        // Add icon
        $iconEmoji = $setting?->getSetting('icon_emoji') ?? 
                    config('log-management.notifications.channels.slack.icon_emoji');
        $iconUrl = $setting?->getSetting('icon_url') ?? 
                  config('log-management.notifications.channels.slack.icon_url');

        if ($iconEmoji) {
            $payload['icon_emoji'] = $iconEmoji;
        } elseif ($iconUrl) {
            $payload['icon_url'] = $iconUrl;
        }

        return $payload;
    }

    /**
     * Get the main Slack message text.
     */
    protected function getSlackText(array $logData): string
    {
        $emoji = $this->getLevelEmoji($logData['level']);
        $level = strtoupper($logData['level']);
        $environment = $logData['environment'];
        
        return "{$emoji} Log Alert: {$level} level log detected in {$environment}";
    }

    /**
     * Build Slack attachment fields.
     */
    protected function buildSlackFields(array $logData): array
    {
        $fields = [
            [
                'title' => 'Level',
                'value' => strtoupper($logData['level']),
                'short' => true,
            ],
            [
                'title' => 'Environment',
                'value' => $logData['environment'],
                'short' => true,
            ],
            [
                'title' => 'Timestamp',
                'value' => $logData['timestamp'],
                'short' => true,
            ],
        ];

        if (!empty($logData['url'])) {
            $fields[] = [
                'title' => 'URL',
                'value' => $logData['url'],
                'short' => false,
            ];
        }

        if (!empty($logData['context'])) {
            $contextStr = json_encode($logData['context'], JSON_PRETTY_PRINT);
            if (strlen($contextStr) > 500) {
                $contextStr = substr($contextStr, 0, 500) . '...';
            }
            
            $fields[] = [
                'title' => 'Context',
                'value' => "```{$contextStr}```",
                'short' => false,
            ];
        }

        return $fields;
    }

    /**
     * Get Slack color based on log level.
     */
    protected function getSlackColor(string $level): string
    {
        return match (strtolower($level)) {
            'emergency', 'alert', 'critical', 'error' => 'danger',
            'warning' => 'warning',
            'notice', 'info' => 'good',
            'debug' => '#439FE0',
            default => '#808080',
        };
    }

    /**
     * Get emoji for log level.
     */
    protected function getLevelEmoji(string $level): string
    {
        return match (strtolower($level)) {
            'emergency' => '🆘',
            'alert' => '🚨',
            'critical' => '💥',
            'error' => '❌',
            'warning' => '⚠️',
            'notice' => '📢',
            'info' => 'ℹ️',
            'debug' => '🐛',
            default => '📝',
        };
    }
}