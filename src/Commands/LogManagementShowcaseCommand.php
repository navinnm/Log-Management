<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Log;

class LogManagementShowcaseCommand extends Command
{
    protected $signature = 'log-management:showcase 
                            {--template=all : Which template to showcase (email, slack, all)}
                            {--level=error : Log level to test}
                            {--interactive : Run in interactive mode}';

    protected $description = 'Showcase the professional notification templates with realistic data';

    protected LogNotifierService $notifierService;

    public function __construct(LogNotifierService $notifierService)
    {
        parent::__construct();
        $this->notifierService = $notifierService;
    }

    public function handle(): int
    {
        $this->displayHeader();

        if ($this->option('interactive')) {
            return $this->runInteractiveMode();
        }

        $template = $this->option('template');
        $level = $this->option('level');

        return $this->runShowcase($template, $level);
    }

    protected function displayHeader(): void
    {
        $this->info('');
        $this->line('  <fg=cyan>╔══════════════════════════════════════════════════════════════╗</>');
        $this->line('  <fg=cyan>║</> <fg=white;options=bold>🎨 Log Management Professional Templates Showcase</> <fg=cyan>║</>');
        $this->line('  <fg=cyan>╚══════════════════════════════════════════════════════════════╝</>');
        $this->info('');
    }

    protected function runInteractiveMode(): int
    {
        $this->info('🚀 Welcome to the Interactive Template Showcase!');
        $this->newLine();

        // Choose template type
        $template = $this->choice(
            'Which template would you like to showcase?',
            ['email', 'slack', 'both'],
            'both'
        );

        // Choose error scenario
        $scenario = $this->choice(
            'Which error scenario would you like to demonstrate?',
            [
                'database-connection' => '🔌 Database Connection Failure',
                'permission-error' => '🔒 File Permission Error',
                'memory-exhausted' => '💾 Memory Limit Exceeded',
                'api-timeout' => '⏰ External API Timeout',
                'validation-error' => '📝 Form Validation Error',
                'authentication-failed' => '🛡️ Authentication Failure',
                'custom' => '✏️ Custom Error Message'
            ]
        );

        if ($scenario === 'custom') {
            $customMessage = $this->ask('Enter your custom error message:');
            $logData = $this->buildCustomLogData($customMessage);
        } else {
            $logData = $this->buildScenarioLogData($scenario);
        }

        // Confirm before sending
        $this->displayPreview($logData);
        
        if (!$this->confirm('Send this showcase notification?', true)) {
            $this->warn('Showcase cancelled.');
            return self::SUCCESS;
        }

        return $this->sendShowcaseNotification($template, $logData);
    }

    protected function runShowcase(string $template, string $level): int
    {
        $this->info("🎭 Running template showcase for: <fg=yellow>{$template}</> at <fg=red>{$level}</> level");
        $this->newLine();

        // Generate realistic test data
        $logData = $this->generateRealisticLogData($level);
        
        $this->displayPreview($logData);
        
        return $this->sendShowcaseNotification($template, $logData);
    }

    protected function generateRealisticLogData(string $level): array
    {
        $scenarios = [
            'error' => [
                'message' => 'Connection to database server failed: SQLSTATE[HY000] [2002] Connection refused',
                'file_path' => '/app/Http/Controllers/UserController.php',
                'line_number' => 47,
                'url' => config('app.url') . '/users/profile/edit',
                'method' => 'POST',
                'execution_time' => 2350,
                'memory_usage' => 145 * 1024 * 1024,
                'context' => [
                    'user_id' => 'usr_7834hf834h',
                    'operation' => 'update_profile',
                    'attempted_fields' => ['email', 'name', 'avatar'],
                    'database_host' => 'mysql-primary-01.internal',
                    'connection_pool' => 'web-pool-3',
                    'retry_count' => 3,
                    'last_successful_query' => '2024-06-07 20:45:23',
                    'user_actions' => [
                        ['time' => '20:58:41', 'description' => 'User submitted profile form'],
                        ['time' => '20:58:35', 'description' => 'User uploaded new avatar image'],
                        ['time' => '20:57:12', 'description' => 'User navigated to profile edit page'],
                    ]
                ],
            ],
            'critical' => [
                'message' => 'Payment gateway API returned HTTP 500: Internal server error during transaction processing',
                'file_path' => '/app/Services/PaymentService.php',
                'line_number' => 123,
                'url' => config('app.url') . '/checkout/process',
                'method' => 'POST',
                'execution_time' => 8750,
                'memory_usage' => 89 * 1024 * 1024,
                'context' => [
                    'transaction_id' => 'txn_948573hf834',
                    'amount' => '$149.99',
                    'currency' => 'USD',
                    'payment_method' => 'stripe_card',
                    'customer_id' => 'cus_84hf7834hf8',
                    'gateway_response_time' => '8.2s',
                    'retry_attempts' => 2,
                    'order_items_count' => 3,
                    'user_actions' => [
                        ['time' => '21:05:15', 'description' => 'User clicked "Complete Purchase" button'],
                        ['time' => '21:04:58', 'description' => 'User entered credit card information'],
                        ['time' => '21:02:34', 'description' => 'User reviewed order summary'],
                    ]
                ],
            ],
            'warning' => [
                'message' => 'Redis cache server response time exceeded threshold: 2.1s (threshold: 1.0s)',
                'file_path' => '/app/Services/CacheService.php',
                'line_number' => 89,
                'url' => config('app.url') . '/dashboard',
                'method' => 'GET',
                'execution_time' => 2156,
                'memory_usage' => 67 * 1024 * 1024,
                'context' => [
                    'cache_key' => 'user_dashboard_data_' . uniqid(),
                    'cache_server' => 'redis-cache-02.internal',
                    'response_time' => '2.134s',
                    'cache_hit_rate' => '87.3%',
                    'concurrent_connections' => 423,
                    'memory_usage_percent' => '78.2%',
                    'user_actions' => [
                        ['time' => '21:12:08', 'description' => 'User refreshed dashboard page'],
                        ['time' => '21:11:45', 'description' => 'User applied date filter'],
                        ['time' => '21:10:23', 'description' => 'User logged into dashboard'],
                    ]
                ],
            ],
        ];

        $scenarioData = $scenarios[$level] ?? $scenarios['error'];

        return array_merge([
            'level' => $level,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'user_id' => 'usr_showcase_' . uniqid(),
            'session_id' => 'sess_showcase_' . uniqid(),
            'request_id' => 'req_showcase_' . uniqid(),
            'ip_address' => '192.168.1.' . rand(100, 200),
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'channel' => 'showcase',
            'stack_trace' => $this->generateRealisticStackTrace($scenarioData['file_path'], $scenarioData['line_number']),
            'extra' => [
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
                'server_name' => 'web-server-' . rand(1, 5) . '.production.local',
                'load_average' => [rand(100, 300)/100, rand(80, 250)/100, rand(70, 200)/100],
                'disk_usage' => rand(45, 85) . '%',
            ]
        ], $scenarioData);
    }

    protected function buildCustomLogData(string $customMessage): array
    {
        return [
            'level' => 'error',
            'message' => $customMessage,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'file_path' => '/app/Http/Controllers/CustomController.php',
            'line_number' => rand(50, 200),
            'url' => config('app.url') . '/custom/action',
            'method' => 'POST',
            'execution_time' => rand(200, 3000),
            'memory_usage' => rand(30, 150) * 1024 * 1024,
            'user_id' => 'usr_custom_' . uniqid(),
            'session_id' => 'sess_custom_' . uniqid(),
            'request_id' => 'req_custom_' . uniqid(),
            'ip_address' => '192.168.1.' . rand(1, 255),
            'user_agent' => 'Mozilla/5.0 (Custom Browser) AppleWebKit/537.36',
            'channel' => 'custom',
            'context' => [
                'custom_data' => true,
                'user_input' => substr($customMessage, 0, 50),
                'timestamp' => now()->toISOString(),
            ],
            'stack_trace' => $this->generateRealisticStackTrace('/app/Http/Controllers/CustomController.php', rand(50, 200)),
        ];
    }

    protected function generateRealisticStackTrace(string $filePath, int $lineNumber): string
    {
        $frames = [
            "#{0} {$filePath}({$lineNumber}): App\\Http\\Controllers\\Controller->handleError()",
            "#1 /vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array()",
            "#2 /vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(45): Illuminate\\Routing\\Controller->callAction()",
            "#3 /vendor/laravel/framework/src/Illuminate/Routing/Route.php(262): Illuminate\\Routing\\ControllerDispatcher->dispatch()",
            "#4 /vendor/laravel/framework/src/Illuminate/Routing/Route.php(205): Illuminate\\Routing\\Route->runController()",
            "#5 /vendor/laravel/framework/src/Illuminate/Routing/Router.php(721): Illuminate\\Routing\\Route->run()",
            "#6 /vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(141): Illuminate\\Routing\\Router->Illuminate\\Routing\\{closure}()",
            "#7 /app/Http/Middleware/CheckForMaintenanceMode.php(62): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()",
            "#8 /vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(180): App\\Http\\Middleware\\CheckForMaintenanceMode->handle()",
            "#9 /vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Pipeline\\Pipeline->then()",
            "#10 /vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(169): Illuminate\\Foundation\\Http\\Kernel->sendRequestThroughRouter()",
            "#11 /public/index.php(55): Illuminate\\Foundation\\Http\\Kernel->handle()",
            "#12 {main}"
        ];

        return implode("\n", $frames);
    }

    protected function displayPreview(array $logData): void
    {
        $this->info('📋 Notification Preview:');
        $this->newLine();

        // Create a nice preview table
        $previewData = [
            ['Field', 'Value'],
            ['Level', strtoupper($logData['level'])],
            ['Message', Str::limit($logData['message'], 80)],
            ['Environment', $logData['environment']],
            ['File', basename($logData['file_path'] ?? 'N/A')],
            ['Line', $logData['line_number'] ?? 'N/A'],
            ['Execution Time', ($logData['execution_time'] ?? 0) . 'ms'],
            ['Memory Usage', round(($logData['memory_usage'] ?? 0) / 1024 / 1024, 1) . 'MB'],
            ['User ID', $logData['user_id'] ?? 'N/A'],
            ['IP Address', $logData['ip_address'] ?? 'N/A'],
        ];

        $this->table($previewData[0], array_slice($previewData, 1));
        $this->newLine();
    }

    protected function sendShowcaseNotification(string $template, array $logData): int
    {
        $this->info('🚀 Sending showcase notification(s)...');
        $this->newLine();

        $results = [];

        try {
            if ($template === 'email' || $template === 'all' || $template === 'both') {
                $this->line('📧 Testing Email Template...');
                $emailResult = $this->testEmailTemplate($logData);
                $results['email'] = $emailResult;
                
                if ($emailResult['success']) {
                    $this->info('   ✅ Email template sent successfully!');
                } else {
                    $this->error('   ❌ Email template failed: ' . $emailResult['message']);
                }
            }

            if ($template === 'slack' || $template === 'all' || $template === 'both') {
                $this->line('💬 Testing Slack Template...');
                $slackResult = $this->testSlackTemplate($logData);
                $results['slack'] = $slackResult;
                
                if ($slackResult['success']) {
                    $this->info('   ✅ Slack template sent successfully!');
                } else {
                    $this->error('   ❌ Slack template failed: ' . $slackResult['message']);
                }
            }

            $this->newLine();
            $this->displayResults($results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Showcase failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function testEmailTemplate(array $logData): array
    {
        try {
            // Check if email is configured
            $emailChannel = $this->notifierService->getChannels()['email'] ?? null;
            
            if (!$emailChannel) {
                return ['success' => false, 'message' => 'Email channel not configured'];
            }

            if (!$emailChannel->isEnabled()) {
                return ['success' => false, 'message' => 'Email channel not enabled'];
            }

            if (!$emailChannel->validateConfiguration()) {
                return ['success' => false, 'message' => 'Email configuration invalid'];
            }

            // Send the test email
            $result = $emailChannel->send($logData);
            
            return [
                'success' => $result,
                'message' => $result ? 'Professional email template sent!' : 'Failed to send email'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function testSlackTemplate(array $logData): array
    {
        try {
            // Check if slack is configured
            $slackChannel = $this->notifierService->getChannels()['slack'] ?? null;
            
            if (!$slackChannel) {
                return ['success' => false, 'message' => 'Slack channel not configured'];
            }

            if (!$slackChannel->isEnabled()) {
                return ['success' => false, 'message' => 'Slack channel not enabled'];
            }

            if (!$slackChannel->validateConfiguration()) {
                return ['success' => false, 'message' => 'Slack configuration invalid'];
            }

            // Send the test slack message
            $result = $slackChannel->send($logData);
            
            return [
                'success' => $result,
                'message' => $result ? 'Professional Slack message sent!' : 'Failed to send Slack message'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function displayResults(array $results): void
    {
        $this->info('📊 Showcase Results Summary:');
        $this->newLine();

        $tableData = [['Channel', 'Status', 'Message']];
        
        foreach ($results as $channel => $result) {
            $status = $result['success'] ? '<fg=green>✅ SUCCESS</>' : '<fg=red>❌ FAILED</>';
            $tableData[] = [
                ucfirst($channel),
                $status,
                Str::limit($result['message'], 50)
            ];
        }

        $this->table($tableData[0], array_slice($tableData, 1));

        $this->newLine();
        $this->comment('💡 Tips for better results:');
        $this->line('   • For Email: Configure SMTP settings and set LOG_MANAGEMENT_EMAIL_TO');
        $this->line('   • For Slack: Set up a webhook URL with LOG_MANAGEMENT_SLACK_WEBHOOK');
        $this->line('   • Enable channels in config: LOG_MANAGEMENT_EMAIL_ENABLED=true');
        $this->newLine();

        // Show next steps
        $this->info('🎯 Next Steps:');
        $this->line('   1. Check your email inbox or Slack channel');
        $this->line('   2. Review the professional template design');
        $this->line('   3. Test with real errors: php artisan log-management:debug --generate-logs=3');
        $this->line('   4. Configure notification settings for production use');
        $this->newLine();

        // Show configuration commands
        $this->comment('⚙️ Quick Configuration Commands:');
        $this->line('   php artisan log-management:test --channel=email');
        $this->line('   php artisan log-management:test --channel=slack');
        $this->line('   php artisan tinker # Then: NotificationSetting::createDefaults()');
    }
}