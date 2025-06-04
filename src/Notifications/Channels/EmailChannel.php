<?php

namespace Fulgid\LogManagement\Notifications\Channels;

use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailChannel implements NotificationChannelInterface
{
    protected string $name = 'email';

    /**
     * Send a notification through email.
     */
    public function send(array $logData): bool
    {
        try {
            $setting = NotificationSetting::forChannel($this->name)->first();
            
            if (!$setting || !$setting->shouldNotify($logData)) {
                return false;
            }

            $to = $setting->getSetting('to') ?? config('log-management.notifications.channels.email.to');
            $from = $setting->getSetting('from') ?? config('log-management.notifications.channels.email.from', config('mail.from.address'));
            $fromName = $setting->getSetting('from_name') ?? config('log-management.notifications.channels.email.from_name', config('mail.from.name'));

            if (!$to) {
                Log::channel('single')->warning('Email notification skipped: No recipient configured');
                return false;
            }

            Mail::send('log-management::emails.log-notification', [
                'logData' => $logData,
                'level' => strtoupper($logData['level']),
                'setting' => $setting,
            ], function ($message) use ($to, $from, $fromName, $logData, $setting) {
                $message->to($to)
                    ->from($from, $fromName)
                    ->subject($this->getSubject($logData, $setting));
            });

            $setting->markAsNotified();
            
            return true;
        } catch (\Exception $e) {
            Log::channel('single')->error('Failed to send email notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if this notification channel is enabled.
     */
    public function isEnabled(): bool
    {
        if (!config('log-management.notifications.channels.email.enabled', false)) {
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
        $to = $setting?->getSetting('to') ?? config('log-management.notifications.channels.email.to');
        
        return !empty($to) && filter_var($to, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Get the channel configuration requirements.
     */
    public function getConfigurationRequirements(): array
    {
        return [
            'to' => 'Recipient email address',
            'from' => 'Sender email address (optional)',
            'from_name' => 'Sender name (optional)',
            'subject_prefix' => 'Email subject prefix (optional)',
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
                    'message' => 'Invalid email configuration',
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
                'message' => $result ? 'Test email sent successfully' : 'Failed to send test email',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get the email subject.
     */
    protected function getSubject(array $logData, ?NotificationSetting $setting = null): string
    {
        $prefix = $setting?->getSetting('subject_prefix') ?? 
                  config('log-management.notifications.channels.email.subject_prefix', '[LOG ALERT]');
        
        $level = strtoupper($logData['level']);
        $environment = strtoupper($logData['environment']);
        
        return "{$prefix} {$level} in {$environment}";
    }

    /**
     * Get email priority based on log level.
     */
    protected function getPriority(string $level): int
    {
        return match (strtolower($level)) {
            'emergency', 'alert', 'critical' => 1, // High priority
            'error' => 2, // Normal priority
            'warning' => 3, // Low priority
            default => 3,
        };
    }

    /**
     * Format log context for email display.
     */
    protected function formatContext(array $context): string
    {
        if (empty($context)) {
            return 'No additional context';
        }

        $formatted = [];
        foreach ($context as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_PRETTY_PRINT);
            }
            $formatted[] = "{$key}: {$value}";
        }

        return implode("\n", $formatted);
    }
}