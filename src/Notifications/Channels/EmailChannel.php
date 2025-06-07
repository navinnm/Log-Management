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
            
            // Create setting if it doesn't exist
            if (!$setting) {
                $setting = NotificationSetting::create([
                    'channel' => $this->name,
                    'enabled' => config('log-management.notifications.channels.email.enabled', false),
                    'settings' => NotificationSetting::getDefaultSettings($this->name),
                ]);
            }
            
            if (!$setting->shouldNotify($logData)) {
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

            // Don't call markAsNotified here - let the service handle stats
            return true;
        } catch (\Exception $e) {
            Log::channel('single')->error('Failed to send email notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if channel is enabled (database setting or config fallback).
     */
    protected function isChannelEnabled(?NotificationSetting $setting): bool
    {
        // If we have a database setting, use it
        if ($setting) {
            return $setting->enabled;
        }

        // Fall back to configuration
        return config('log-management.notifications.channels.email.enabled', false);
    }

    /**
     * Get email configuration from setting or config.
     */
    protected function getEmailConfiguration(?NotificationSetting $setting): array
    {
        $config = [
            'to' => $setting?->getSetting('to') ?? config('log-management.notifications.channels.email.to'),
            'from' => $setting?->getSetting('from') ?? config('log-management.notifications.channels.email.from', config('mail.from.address')),
            'from_name' => $setting?->getSetting('from_name') ?? config('log-management.notifications.channels.email.from_name', config('mail.from.name')),
            'subject_prefix' => $setting?->getSetting('subject_prefix') ?? config('log-management.notifications.channels.email.subject_prefix', '[LOG ALERT]'),
        ];

        // Also check environment variables as fallback
        if (!$config['to']) {
            $config['to'] = env('LOG_MANAGEMENT_EMAIL_TO');
        }
        if (!$config['from']) {
            $config['from'] = env('LOG_MANAGEMENT_EMAIL_FROM', env('MAIL_FROM_ADDRESS'));
        }
        if (!$config['from_name']) {
            $config['from_name'] = env('LOG_MANAGEMENT_EMAIL_FROM_NAME', env('MAIL_FROM_NAME'));
        }

        return $config;
    }

    /**
     * Check if this notification channel is enabled.
     */
    public function isEnabled(): bool
    {
        // Check main notifications enabled
        if (!config('log-management.notifications.enabled', true)) {
            return false;
        }

        // Check if email channel is enabled
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
        $config = $this->getEmailConfiguration(null);
        
        return !empty($config['to']) && filter_var($config['to'], FILTER_VALIDATE_EMAIL);
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
        $config = $this->getEmailConfiguration($setting);
        $prefix = $config['subject_prefix'];
        
        $level = strtoupper($logData['level']);
        $environment = strtoupper($logData['environment']);
        
        return "{$prefix} {$level} in {$environment}";
    }

    /**
     * Check if log level should trigger notification.
     */
    protected function shouldNotifyForLevel(array $logData): bool
    {
        $notificationLevels = config('log-management.notifications.levels', ['error', 'critical', 'emergency']);
        
        return in_array(strtolower($logData['level']), array_map('strtolower', $notificationLevels));
    }
}