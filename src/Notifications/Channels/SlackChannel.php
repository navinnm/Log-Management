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
     * Send a professional Slack notification with rich formatting.
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
                Log::warning('Slack notification skipped: No webhook URL configured');
                return false;
            }

            $payload = $this->buildProfessionalSlackPayload($logData, $setting);
            
            $response = Http::timeout(30)->post($webhookUrl, $payload);

            if ($response->successful()) {
                $setting->markAsNotified();
                return true;
            }

            Log::error('Slack notification failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build professional Slack payload with rich blocks and interactive elements.
     */
    protected function buildProfessionalSlackPayload(array $logData, ?NotificationSetting $setting = null): array
    {
        $level = strtoupper($logData['level']);
        $environment = $logData['environment'] ?? 'Production';
        $message = $logData['message'];
        $timestamp = $logData['timestamp'] ?? now()->toISOString();

        // Get color and emoji based on severity
        $color = $this->getSeverityColor($logData['level']);
        $emoji = $this->getSeverityEmoji($logData['level']);
        $urgency = $this->getUrgencyLevel($logData['level']);

        // Build main payload
        $payload = [
            'username' => $setting?->getSetting('username') ?? 
                         config('log-management.notifications.channels.slack.username', '🚨 Error Monitor'),
            'channel' => $setting?->getSetting('channel') ?? 
                        config('log-management.notifications.channels.slack.channel', '#alerts'),
            'icon_emoji' => $emoji,
            'unfurl_links' => false,
            'unfurl_media' => false,
        ];

        // Add mention for critical errors
        $mentionUsers = '';
        if (in_array(strtolower($logData['level']), ['emergency', 'alert', 'critical'])) {
            $mentionUsers = $setting?->getSetting('mention_users') ?? 
                           config('log-management.notifications.channels.slack.mention_users', '');
        }

        // Main message text
        $mainText = $mentionUsers ? $mentionUsers . ' ' : '';
        $mainText .= "*{$urgency} Alert*: {$level} level error detected in *{$environment}*";

        $payload['text'] = $mainText;

        // Rich block-based layout
        $payload['blocks'] = [
            // Header block
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "{$emoji} {$level} Alert - {$environment}",
                    'emoji' => true
                ]
            ],

            // Main error message
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Error Message:*\n```{$message}```"
                ]
            ],

            // Quick info fields
            [
                'type' => 'section',
                'fields' => $this->buildInfoFields($logData)
            ],

            // Performance metrics (if available)
            ...$this->buildPerformanceBlocks($logData),

            // Action buttons
            [
                'type' => 'actions',
                'elements' => $this->buildActionButtons($logData)
            ],

            // Context footer
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => $this->buildContextFooter($logData)
                    ]
                ]
            ]
        ];

        // Add attachment for backward compatibility and additional styling
        $payload['attachments'] = [
            [
                'color' => $color,
                'fallback' => "Error Alert: {$level} - {$message}",
                'footer' => config('app.name') . ' Error Monitoring',
                'footer_icon' => 'https://cdn-icons-png.flaticon.com/32/3094/3094796.png',
                'ts' => strtotime($timestamp),
            ]
        ];

        return $payload;
    }

    /**
     * Build information fields for the Slack message.
     */
    protected function buildInfoFields(array $logData): array
    {
        $fields = [];

        // Essential fields
        $fields[] = [
            'type' => 'mrkdwn',
            'text' => "*Level:*\n`{$logData['level']}`"
        ];

        $fields[] = [
            'type' => 'mrkdwn',
            'text' => "*Environment:*\n`{$logData['environment']}`"
        ];

        // File info if available
        if (!empty($logData['file_path'])) {
            $fileName = basename($logData['file_path']);
            $lineInfo = !empty($logData['line_number']) ? ":{$logData['line_number']}" : '';
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => "*File:*\n`{$fileName}{$lineInfo}`"
            ];
        }

        // URL info if available
        if (!empty($logData['url'])) {
            $path = parse_url($logData['url'], PHP_URL_PATH) ?: $logData['url'];
            $method = !empty($logData['method']) ? strtoupper($logData['method']) . ' ' : '';
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => "*Route:*\n`{$method}{$path}`"
            ];
        }

        // User info if available
        if (!empty($logData['user_id'])) {
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => "*User ID:*\n`{$logData['user_id']}`"
            ];
        }

        // Request ID if available
        if (!empty($logData['request_id'])) {
            $shortId = substr($logData['request_id'], 0, 8);
            $fields[] = [
                'type' => 'mrkdwn',
                'text' => "*Request:*\n`{$shortId}...`"
            ];
        }

        return $fields;
    }

    /**
     * Build performance metric blocks if data is available.
     */
    protected function buildPerformanceBlocks(array $logData): array
    {
        $blocks = [];

        if (!empty($logData['execution_time']) || !empty($logData['memory_usage'])) {
            $performanceText = "*Performance Impact:*\n";
            
            if (!empty($logData['execution_time'])) {
                $time = $logData['execution_time'];
                $timeIcon = $time > 2000 ? '🔴' : ($time > 1000 ? '🟡' : '🟢');
                $performanceText .= "{$timeIcon} *Response Time:* {$time}ms\n";
            }
            
            if (!empty($logData['memory_usage'])) {
                $memory = round($logData['memory_usage'] / 1024 / 1024, 1);
                $memoryIcon = $memory > 128 ? '🔴' : ($memory > 64 ? '🟡' : '🟢');
                $performanceText .= "{$memoryIcon} *Memory Usage:* {$memory}MB\n";
            }

            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $performanceText
                ]
            ];
        }

        return $blocks;
    }

    /**
     * Build action buttons for the Slack message.
     */
    protected function buildActionButtons(array $logData): array
    {
        $buttons = [];

        // Dashboard button
        if (config('app.url')) {
            $buttons[] = [
                'type' => 'button',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '📊 View Dashboard',
                    'emoji' => true
                ],
                'url' => config('app.url') . '/log-management',
                'style' => 'primary'
            ];
        }

        // Reproduce error button
        if (!empty($logData['url'])) {
            $buttons[] = [
                'type' => 'button',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '🔗 Reproduce Error',
                    'emoji' => true
                ],
                'url' => $logData['url']
            ];
        }

        // View details button
        if (!empty($logData['id'])) {
            $buttons[] = [
                'type' => 'button',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '🔍 Details',
                    'emoji' => true
                ],
                'url' => config('app.url') . '/log-management/api/logs/' . $logData['id']
            ];
        }

        return $buttons;
    }

    /**
     * Build context footer with additional information.
     */
    protected function buildContextFooter(array $logData): string
    {
        $parts = [];
        
        if (!empty($logData['ip_address'])) {
            $parts[] = "IP: {$logData['ip_address']}";
        }
        
        if (!empty($logData['channel']) && $logData['channel'] !== 'default') {
            $parts[] = "Channel: {$logData['channel']}";
        }
        
        $timestamp = date('M j, Y \a\t g:i A', strtotime($logData['timestamp'] ?? 'now'));
        $parts[] = "Time: {$timestamp}";
        
        return implode(' • ', $parts);
    }

    /**
     * Get severity color for Slack attachment.
     */
    protected function getSeverityColor(string $level): string
    {
        return match (strtolower($level)) {
            'emergency', 'alert' => '#DC143C',
            'critical' => '#FF0000',
            'error' => '#FF4500',
            'warning' => '#FFA500',
            'notice' => '#1E90FF',
            'info' => '#32CD32',
            'debug' => '#808080',
            default => '#808080',
        };
    }

    /**
     * Get severity emoji for the notification.
     */
    protected function getSeverityEmoji(string $level): string
    {
        return match (strtolower($level)) {
            'emergency' => ':rotating_light:',
            'alert' => ':fire:',
            'critical' => ':boom:',
            'error' => ':x:',
            'warning' => ':warning:',
            'notice' => ':information_source:',
            'info' => ':bulb:',
            'debug' => ':bug:',
            default => ':memo:',
        };
    }

    /**
     * Get urgency level description.
     */
    protected function getUrgencyLevel(string $level): string
    {
        return match (strtolower($level)) {
            'emergency', 'alert' => 'CRITICAL',
            'critical' => 'HIGH PRIORITY',
            'error' => 'ERROR',
            'warning' => 'WARNING',
            'notice', 'info' => 'INFO',
            'debug' => 'DEBUG',
            default => 'UNKNOWN',
        };
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
            'mention_users' => 'Users to mention for critical alerts (optional)',
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
                    'message' => 'Invalid Slack configuration - webhook URL required',
                ];
            }

            $testLogData = [
                'message' => '🧪 Test notification from Log Management Package',
                'level' => 'info',
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'context' => ['test' => true],
                'url' => config('app.url'),
                'execution_time' => 250,
                'memory_usage' => 32 * 1024 * 1024, // 32MB
                'file_path' => '/app/Http/Controllers/TestController.php',
                'line_number' => 42,
                'user_id' => 'test_user',
                'ip_address' => '127.0.0.1',
                'channel' => 'testing',
            ];

            $result = $this->send($testLogData);

            return [
                'success' => $result,
                'message' => $result ? 
                    '✅ Test Slack message sent successfully! Check your Slack channel.' : 
                    '❌ Failed to send test Slack message. Check your webhook URL and channel settings.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Slack test failed: ' . $e->getMessage(),
            ];
        }
    }
}