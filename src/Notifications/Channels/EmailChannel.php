<?php

namespace Fulgid\LogManagement\Notifications\Channels;

use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Mailable;

class EmailChannel implements NotificationChannelInterface
{
    protected string $name = 'email';

    /**
     * Send a professional email notification.
     */
    public function send(array $logData): bool
    {
        try {
            $setting = NotificationSetting::forChannel($this->name)->first();
            
            if (!$setting) {
                $setting = $this->createDefaultSetting();
            }
            
            if (!$setting->shouldNotify($logData)) {
                return false;
            }

            $config = $this->getEmailConfiguration($setting);
            
            if (!$config['to']) {
                Log::warning('Email notification skipped: No recipient configured');
                return false;
            }

            // Create and send the mailable
            $mailable = new LogNotificationMail($logData, $config);
            
            Mail::to($config['to'])
                ->send($mailable);

            $setting->markAsNotified();
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send email notification: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'log_data' => $logData
            ]);
            return false;
        }
    }

    /**
     * Create default notification setting.
     */
    protected function createDefaultSetting(): NotificationSetting
    {
        return NotificationSetting::create([
            'channel' => $this->name,
            'enabled' => config('log-management.notifications.channels.email.enabled', false),
            'settings' => NotificationSetting::getDefaultSettings($this->name),
            'conditions' => [
                'levels' => ['error', 'critical', 'emergency', 'alert'],
                'environments' => ['production', 'staging'],
            ],
        ]);
    }

    /**
     * Get email configuration from setting or config.
     */
    protected function getEmailConfiguration(?NotificationSetting $setting): array
    {
        return [
            'to' => $setting?->getSetting('to') ?? 
                   config('log-management.notifications.channels.email.to') ??
                   env('LOG_MANAGEMENT_EMAIL_TO'),
            'from' => $setting?->getSetting('from') ?? 
                     config('log-management.notifications.channels.email.from') ??
                     config('mail.from.address'),
            'from_name' => $setting?->getSetting('from_name') ?? 
                          config('log-management.notifications.channels.email.from_name') ??
                          config('mail.from.name'),
            'subject_prefix' => $setting?->getSetting('subject_prefix') ?? 
                               config('log-management.notifications.channels.email.subject_prefix', '[LOG ALERT]'),
            'template' => $setting?->getSetting('template') ?? 
                         'log-management::emails.professional-notification',
        ];
    }

    /**
     * Check if this notification channel is enabled.
     */
    public function isEnabled(): bool
    {
        if (!config('log-management.notifications.enabled', true)) {
            return false;
        }

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
        
        $hasRecipient = !empty($config['to']) && filter_var($config['to'], FILTER_VALIDATE_EMAIL);
        $hasFrom = !empty($config['from']) && filter_var($config['from'], FILTER_VALIDATE_EMAIL);
        
        return $hasRecipient && $hasFrom;
    }

    /**
     * Get the channel configuration requirements.
     */
    public function getConfigurationRequirements(): array
    {
        return [
            'to' => 'Recipient email address (required)',
            'from' => 'Sender email address (optional, uses mail.from.address)',
            'from_name' => 'Sender name (optional, uses mail.from.name)',
            'subject_prefix' => 'Email subject prefix (optional, defaults to [LOG ALERT])',
            'template' => 'Email template name (optional)',
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
                    'message' => 'Invalid email configuration - recipient and sender email required',
                ];
            }

            $testLogData = [
                'message' => '🧪 Test notification from Log Management Package',
                'level' => 'info',
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'context' => [
                    'test' => true,
                    'generated_by' => 'test-command',
                    'user_actions' => [
                        ['time' => '10:30:25', 'description' => 'User clicked test button'],
                        ['time' => '10:30:20', 'description' => 'User navigated to settings page'],
                    ]
                ],
                'url' => config('app.url') . '/test',
                'method' => 'GET',
                'execution_time' => 150,
                'memory_usage' => 24 * 1024 * 1024, // 24MB
                'file_path' => '/app/Http/Controllers/TestController.php',
                'line_number' => 42,
                'user_id' => 'test_user_123',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Test Browser)',
                'session_id' => 'test_session_' . uniqid(),
                'request_id' => 'req_' . uniqid(),
                'channel' => 'testing',
                'stack_trace' => "#0 /app/Http/Controllers/TestController.php(42): TestController->testMethod()\n#1 /vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array()\n#2 /vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(45): Illuminate\\Routing\\Controller->callAction()",
                'extra' => [
                    'php_version' => phpversion(),
                    'laravel_version' => app()->version(),
                    'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
                ]
            ];

            $result = $this->send($testLogData);

            return [
                'success' => $result,
                'message' => $result ? 
                    '✅ Test email sent successfully! Check your inbox.' : 
                    '❌ Failed to send test email. Check your SMTP configuration and recipient address.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage(),
            ];
        }
    }
}

/**
 * Professional Log Notification Mailable
 */
class LogNotificationMail extends Mailable
{
    public array $logData;
    public array $config;

    public function __construct(array $logData, array $config)
    {
        $this->logData = $logData;
        $this->config = $config;
    }

    public function build()
    {
        $subject = $this->getSubject();
        
        return $this->from($this->config['from'], $this->config['from_name'])
                    ->subject($subject)
                    ->view($this->config['template'])
                    ->with([
                        'logData' => $this->logData,
                        'config' => $this->config,
                        'level' => strtoupper($this->logData['level']),
                        'appName' => config('app.name'),
                        'appUrl' => config('app.url'),
                    ]);
    }

    /**
     * Get the email subject line.
     */
    protected function getSubject(): string
    {
        $prefix = $this->config['subject_prefix'];
        $level = strtoupper($this->logData['level']);
        $environment = strtoupper($this->logData['environment'] ?? 'UNKNOWN');
        $appName = config('app.name');
        
        // Add urgency indicators for critical errors
        $urgencyIndicator = '';
        if (in_array(strtolower($this->logData['level']), ['emergency', 'alert', 'critical'])) {
            $urgencyIndicator = '🚨 URGENT: ';
        }
        
        // Build contextual subject
        $context = '';
        if (!empty($this->logData['file_path'])) {
            $fileName = basename($this->logData['file_path']);
            $context = " in {$fileName}";
        } elseif (!empty($this->logData['url'])) {
            $path = parse_url($this->logData['url'], PHP_URL_PATH);
            $context = " on {$path}";
        }
        
        return "{$urgencyIndicator}{$prefix} {$level} in {$environment}{$context} - {$appName}";
    }
}

/**
 * Blade template helper functions
 */
if (!function_exists('getErrorSuggestions')) {
    function getErrorSuggestions(array $logData): array
    {
        $suggestions = [];
        $errorMessage = strtolower($logData['message']);
        
        // Enhanced AI-like error pattern matching
        if (str_contains($errorMessage, 'connection') && (str_contains($errorMessage, 'refused') || str_contains($errorMessage, 'timeout'))) {
            $suggestions[] = [
                'title' => 'Database Connection Issue',
                'desc' => 'Check database credentials in .env, verify DB server status, and test network connectivity.',
                'priority' => 'high',
                'icon' => '🔌'
            ];
        } elseif (str_contains($errorMessage, 'permission denied') || str_contains($errorMessage, 'forbidden')) {
            $suggestions[] = [
                'title' => 'File/Directory Permissions',
                'desc' => 'Run: sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 755 storage',
                'priority' => 'high',
                'icon' => '🔒'
            ];
        } elseif (str_contains($errorMessage, 'class') && str_contains($errorMessage, 'not found')) {
            $suggestions[] = [
                'title' => 'Autoloader Issue',
                'desc' => 'Execute: composer dump-autoload -o to regenerate class mappings.',
                'priority' => 'medium',
                'icon' => '📦'
            ];
        } elseif (str_contains($errorMessage, 'undefined method') || str_contains($errorMessage, 'call to undefined')) {
            $suggestions[] = [
                'title' => 'Method/Function Missing',
                'desc' => 'Check for typos in method names or verify the class implements required interfaces.',
                'priority' => 'medium',
                'icon' => '🔧'
            ];
        } elseif (str_contains($errorMessage, 'memory') && str_contains($errorMessage, 'exhausted')) {
            $suggestions[] = [
                'title' => 'Memory Limit Exceeded',
                'desc' => 'Increase memory_limit in php.ini or optimize code to reduce memory usage.',
                'priority' => 'high',
                'icon' => '💾'
            ];
        } elseif (str_contains($errorMessage, 'csrf') || str_contains($errorMessage, 'token mismatch')) {
            $suggestions[] = [
                'title' => 'CSRF Token Issue',
                'desc' => 'Ensure @csrf directive is present in forms or check token expiration settings.',
                'priority' => 'medium',
                'icon' => '🛡️'
            ];
        }

        // Performance-based suggestions
        if (!empty($logData['execution_time']) && $logData['execution_time'] > 2000) {
            $suggestions[] = [
                'title' => 'Performance Issue Detected',
                'desc' => 'Response time exceeds 2s. Consider query optimization, caching, or code profiling.',
                'priority' => 'medium',
                'icon' => '⚡'
            ];
        }

        if (!empty($logData['memory_usage']) && $logData['memory_usage'] > 128*1024*1024) {
            $suggestions[] = [
                'title' => 'High Memory Usage',
                'desc' => 'Memory usage >128MB detected. Review memory-intensive operations.',
                'priority' => 'medium',
                'icon' => '📊'
            ];
        }

        // Default suggestion
        if (empty($suggestions)) {
            $suggestions[] = [
                'title' => 'General Debugging Steps',
                'desc' => 'Check Laravel logs, verify environment configuration, and review recent code changes.',
                'priority' => 'low',
                'icon' => '🔍'
            ];
        }

        // Sort by priority
        usort($suggestions, function($a, $b) {
            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
            return ($priorities[$b['priority']] ?? 1) - ($priorities[$a['priority']] ?? 1);
        });

        return $suggestions;
    }
}