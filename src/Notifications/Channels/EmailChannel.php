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
            // Log attempt
            Log::info('EmailChannel: Attempting to send email', [
                'log_data' => $logData,
                'config_check' => $this->getConfigurationStatus(),
            ]);

            $setting = NotificationSetting::forChannel($this->name)->first();
            
            // Log setting status
            Log::info('EmailChannel: Notification setting status', [
                'setting_exists' => $setting !== null,
                'setting_enabled' => $setting ? $setting->enabled : false,
                'log_data' => $logData,
            ]);

            // Create default setting if none exists and email is configured
            if (!$setting && $this->hasValidConfiguration()) {
                $setting = $this->createDefaultNotificationSetting();
                Log::info('EmailChannel: Created default notification setting', [
                    'setting' => $setting->toArray(),
                ]);
            }

            if (!$setting) {
                Log::warning('EmailChannel: No notification setting available and cannot create default');
                return false;
            }

            // Check if notification should be sent based on setting conditions
            if (!$setting->shouldNotify($logData)) {
                Log::info('EmailChannel: Notification filtered out by setting conditions', [
                    'setting_conditions' => $setting->conditions,
                    'log_data' => $logData,
                ]);
                return false;
            }

            $to = $setting->getSetting('to') ?? config('log-management.notifications.channels.email.to');
            $from = $setting->getSetting('from') ?? config('log-management.notifications.channels.email.from', config('mail.from.address'));
            $fromName = $setting->getSetting('from_name') ?? config('log-management.notifications.channels.email.from_name', config('mail.from.name'));

            if (!$to) {
                Log::warning('EmailChannel: No recipient configured', [
                    'setting_to' => $setting->getSetting('to'),
                    'config_to' => config('log-management.notifications.channels.email.to'),
                ]);
                return false;
            }

            // Check if view exists, if not use a simple text email
            $viewExists = view()->exists('log-management::emails.log-notification');
            
            if ($viewExists) {
                Mail::send('log-management::emails.log-notification', [
                    'logData' => $logData,
                    'level' => strtoupper($logData['level']),
                    'setting' => $setting,
                ], function ($message) use ($to, $from, $fromName, $logData, $setting) {
                    $message->to($to)
                        ->from($from, $fromName)
                        ->subject($this->getSubject($logData, $setting));
                });
            } else {
                // Fallback to simple text email
                Mail::raw($this->getTextContent($logData), function ($message) use ($to, $from, $fromName, $logData, $setting) {
                    $message->to($to)
                        ->from($from, $fromName)
                        ->subject($this->getSubject($logData, $setting));
                });
            }

            $setting->markAsNotified();
            
            Log::info('EmailChannel: Email sent successfully', [
                'to' => $to,
                'subject' => $this->getSubject($logData, $setting),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('EmailChannel: Failed to send email notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'log_data' => $logData,
            ]);
            return false;
        }
    }

    /**
     * Check if this notification channel is enabled.
     */
    public function isEnabled(): bool
    {
        $configEnabled = config('log-management.notifications.channels.email.enabled', false);
        $envEnabled = env('LOG_MANAGEMENT_EMAIL_ENABLED', false);
        
        if (!$configEnabled && !$envEnabled) {
            return false;
        }

        $setting = NotificationSetting::forChannel($this->name)->first();
        
        return $setting ? $setting->enabled : true; // Default to enabled if no setting exists
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
        return $this->hasValidConfiguration();
    }

    /**
     * Check if we have valid configuration.
     */
    protected function hasValidConfiguration(): bool
    {
        $setting = NotificationSetting::forChannel($this->name)->first();
        $to = $setting?->getSetting('to') ?? 
              config('log-management.notifications.channels.email.to') ??
              env('LOG_MANAGEMENT_EMAIL_TO');
        
        return !empty($to) && filter_var($to, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Create a default notification setting from configuration.
     */
    protected function createDefaultNotificationSetting(): NotificationSetting
    {
        return NotificationSetting::create([
            'channel' => $this->name,
            'enabled' => true,
            'settings' => [
                'to' => config('log-management.notifications.channels.email.to') ?? env('LOG_MANAGEMENT_EMAIL_TO'),
                'from' => config('log-management.notifications.channels.email.from') ?? config('mail.from.address'),
                'from_name' => config('log-management.notifications.channels.email.from_name') ?? config('mail.from.name'),
                'subject_prefix' => config('log-management.notifications.channels.email.subject_prefix', '[LOG ALERT]'),
            ],
            'conditions' => [
                'levels' => config('log-management.notifications.levels', ['error', 'critical', 'emergency']),
                'environments' => config('log-management.environments', ['production', 'staging']),
            ],
            'rate_limit' => [
                'enabled' => false,
                'max_per_minute' => 10,
                'window_minutes' => 1,
            ],
        ]);
    }

    /**
     * Get configuration requirements.
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
                    'details' => $this->getConfigurationStatus(),
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
                'details' => $this->getConfigurationStatus(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage(),
                'details' => $this->getConfigurationStatus(),
            ];
        }
    }

    /**
     * Get configuration status for debugging.
     */
    protected function getConfigurationStatus(): array
    {
        $setting = NotificationSetting::forChannel($this->name)->first();
        
        return [
            'channel_enabled' => $this->isEnabled(),
            'config_valid' => $this->hasValidConfiguration(),
            'mail_driver' => config('mail.default'),
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
            'log_management_email_enabled' => config('log-management.notifications.channels.email.enabled'),
            'log_management_email_to' => config('log-management.notifications.channels.email.to'),
            'env_email_enabled' => env('LOG_MANAGEMENT_EMAIL_ENABLED'),
            'env_email_to' => env('LOG_MANAGEMENT_EMAIL_TO'),
            'setting_exists' => $setting !== null,
            'setting_enabled' => $setting ? $setting->enabled : null,
        ];
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
     * Get plain text content for fallback email.
     */
    protected function getTextContent(array $logData): string
    {
        $level = strtoupper($logData['level']);
        $environment = $logData['environment'];
        $message = $logData['message'];
        $timestamp = $logData['timestamp'];
        
        $content = "Log Alert: {$level} Level\n";
        $content .= "Environment: {$environment}\n";
        $content .= "Timestamp: {$timestamp}\n";
        $content .= "Message: {$message}\n";
        
        if (!empty($logData['context'])) {
            $content .= "\nContext:\n";
            $content .= json_encode($logData['context'], JSON_PRETTY_PRINT);
        }
        
        $content .= "\n\nThis alert was generated by the Log Management Package.";
        
        return $content;
    }
}