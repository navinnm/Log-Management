<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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

    protected function buildCustomLogData(string $message): array
    {
        return [
            'level' => 'error',
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'user_id' => 'usr_custom_' . uniqid(),
            'session_id' => 'sess_custom_' . uniqid(),
            'request_id' => 'req_custom_' . uniqid(),
            'ip_address' => '192.168.1.' . rand(100, 200),
            'user_agent' => 'Mozilla/5.0 (Custom Test Browser)',
            'channel' => 'custom',
            'url' => config('app.url') . '/custom-test',
            'method' => 'GET',
            'file_path' => '/app/Http/Controllers/CustomController.php',
            'line_number' => rand(50, 200),
            'execution_time' => rand(200, 2000),
            'memory_usage' => rand(30, 120) * 1024 * 1024,
            'stack_trace' => $this->generateRealisticStackTrace('/app/Http/Controllers/CustomController.php', rand(50, 200)),
            'context' => [
                'custom_error' => true,
                'generated_by' => 'showcase_command',
                'timestamp' => now()->toISOString(),
            ]
        ];
    }

    protected function generateRealisticStackTrace(string $filePath, int $lineNumber): string
    {
        $traces = [
            "#0 {$filePath}({$lineNumber}): App\\Services\\DatabaseService->connect()",
            "#1 /vendor/laravel/framework/src/Illuminate/Database/Connection.php(742): PDO->__construct()",
            "#2 /vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php(70): Illuminate\\Database\\Connectors\\MySqlConnector->connect()",
            "#3 /vendor/laravel/framework/src/Illuminate/Database/DatabaseManager.php(358): Illuminate\\Database\\Connectors\\ConnectionFactory->make()",
            "#4 /vendor/laravel/framework/src/Illuminate/Database/Eloquent/Model.php(1327): Illuminate\\Database\\Connection->select()",
            "#5 /app/Http/Controllers/BaseController.php(89): Illuminate\\Database\\Eloquent\\Builder->get()",
            "#6 /vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): App\\Http\\Controllers\\UserController->index()",
            "#7 /vendor/laravel/framework/src/Illuminate/Routing/ControllerDispatcher.php(45): call_user_func_array()",
            "#8 /vendor/laravel/framework/src/Illuminate/Routing/Route.php(262): Illuminate\\Routing\\ControllerDispatcher->dispatch()",
            "#9 /vendor/laravel/framework/src/Illuminate/Routing/Router.php(693): Illuminate\\Routing\\Route->run()",
            "#10 /vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(128): Illuminate\\Routing\\Router->runRoute()",
            "#11 /app/Http/Middleware/CustomMiddleware.php(23): Illuminate\\Pipeline\\Pipeline->Illuminate\\Pipeline\\{closure}()",
            "#12 /vendor/laravel/framework/src/Illuminate/Pipeline/Pipeline.php(167): App\\Http\\Middleware\\CustomMiddleware->handle()",
            "#13 /vendor/laravel/framework/src/Illuminate/Foundation/Http/Kernel.php(200): Illuminate\\Pipeline\\Pipeline->then()",
            "#14 /public/index.php(51): Illuminate\\Foundation\\Http\\Kernel->handle()"
        ];

        return implode("\n", $traces);
    }

    protected function displayPreview(array $logData): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Preview of Showcase Data:</fg=cyan>');
        $this->line('<fg=yellow>═══════════════════════════════════════════════════════════════</>');
        
        $this->table(
            ['Property', 'Value'],
            [
                ['Level', '<fg=red>' . strtoupper($logData['level']) . '</>'],
                ['Message', '<fg=white>' . Str::limit($logData['message'], 60) . '</>'],
                ['Environment', '<fg=green>' . ($logData['environment'] ?? 'Unknown') . '</>'],
                ['File', '<fg=blue>' . ($logData['file_path'] ?? 'N/A') . ':' . ($logData['line_number'] ?? 'N/A') . '</>'],
                ['Execution Time', '<fg=yellow>' . ($logData['execution_time'] ?? 'N/A') . 'ms</>'],
                ['Memory Usage', '<fg=magenta>' . (isset($logData['memory_usage']) ? round($logData['memory_usage'] / 1024 / 1024, 1) . 'MB' : 'N/A') . '</>'],
                ['User ID', '<fg=cyan>' . ($logData['user_id'] ?? 'Anonymous') . '</>'],
                ['IP Address', '<fg=white>' . ($logData['ip_address'] ?? 'Unknown') . '</>'],
            ]
        );
        
        $this->newLine();
    }

    protected function sendShowcaseNotification(string $template, array $logData): int
    {
        $this->info('🚀 Sending showcase notifications...');
        $this->newLine();

        $results = [];
        $progressBar = $this->output->createProgressBar($template === 'both' ? 2 : 1);
        $progressBar->start();

        try {
            if ($template === 'email' || $template === 'both') {
                $this->line('📧 Testing Email Channel...');
                $emailResult = $this->testEmailChannel($logData);
                $results['email'] = $emailResult;
                $progressBar->advance();
            }

            if ($template === 'slack' || $template === 'both') {
                $this->line('💬 Testing Slack Channel...');
                $slackResult = $this->testSlackChannel($logData);
                $results['slack'] = $slackResult;
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display results
            $this->displayResults($results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error('❌ Showcase failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function testEmailChannel(array $logData): array
    {
        try {
            $channels = $this->notifierService->getChannels();
            
            if (!isset($channels['email'])) {
                return [
                    'success' => false,
                    'message' => 'Email channel not configured'
                ];
            }

            $emailChannel = $channels['email'];
            
            if (!$emailChannel->isEnabled()) {
                return [
                    'success' => false,
                    'message' => 'Email channel is disabled'
                ];
            }

            if (!$emailChannel->validateConfiguration()) {
                return [
                    'success' => false,
                    'message' => 'Email configuration is invalid'
                ];
            }

            $result = $emailChannel->send($logData);

            return [
                'success' => $result,
                'message' => $result ? 
                    'Professional email template sent successfully!' : 
                    'Failed to send email notification'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage()
            ];
        }
    }

    protected function testSlackChannel(array $logData): array
    {
        try {
            $channels = $this->notifierService->getChannels();
            
            if (!isset($channels['slack'])) {
                return [
                    'success' => false,
                    'message' => 'Slack channel not configured'
                ];
            }

            $slackChannel = $channels['slack'];
            
            if (!$slackChannel->isEnabled()) {
                return [
                    'success' => false,
                    'message' => 'Slack channel is disabled'
                ];
            }

            if (!$slackChannel->validateConfiguration()) {
                return [
                    'success' => false,
                    'message' => 'Slack configuration is invalid'
                ];
            }

            $result = $slackChannel->send($logData);

            return [
                'success' => $result,
                'message' => $result ? 
                    'Professional Slack notification sent successfully!' : 
                    'Failed to send Slack notification'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Slack test failed: ' . $e->getMessage()
            ];
        }
    }

    protected function displayResults(array $results): void
    {
        $this->line('<fg=cyan>📊 Showcase Results:</fg=cyan>');
        $this->line('<fg=yellow>═══════════════════════════════════════════════════════════════</>');

        foreach ($results as $channel => $result) {
            $icon = $result['success'] ? '✅' : '❌';
            $color = $result['success'] ? 'green' : 'red';
            $channelName = ucfirst($channel);
            
            $this->line("  {$icon} <fg={$color}>{$channelName}:</> {$result['message']}");
        }

        $this->newLine();
        
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $totalCount = count($results);
        
        if ($successCount === $totalCount) {
            $this->info("🎉 All {$totalCount} notification channels sent successfully!");
            $this->line('<fg=green>Your professional templates are working perfectly!</>');
        } elseif ($successCount > 0) {
            $this->warn("⚠️  {$successCount} of {$totalCount} channels sent successfully.");
            $this->line('<fg=yellow>Some channels need configuration adjustments.</fg>');
        } else {
            $this->error("❌ No notifications were sent successfully.");
            $this->line('<fg=red>Please check your notification channel configurations.</fg>');
        }

        $this->newLine();
        $this->line('<fg=cyan>💡 Tips:</fg=cyan>');
        $this->line('  • Check your email templates at: resources/views/vendor/log-management/emails/');
        $this->line('  • Configure notification settings: php artisan log-management:test-notification');
        $this->line('  • View dashboard: ' . config('app.url') . '/log-management');
        $this->newLine();
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

    protected function buildScenarioLogData(string $scenario): array
    {
        $scenarios = [
            'database-connection' => [
                'level' => 'critical',
                'message' => 'Database connection pool exhausted: All 20 connections are in use',
                'file_path' => '/app/Database/ConnectionManager.php',
                'line_number' => 156,
                'execution_time' => 5200,
                'memory_usage' => 178 * 1024 * 1024,
                'context' => [
                    'pool_size' => 20,
                    'active_connections' => 20,
                    'waiting_requests' => 47,
                    'database_host' => 'db-cluster-primary.internal'
                ]
            ],
            'permission-error' => [
                'level' => 'error',
                'message' => 'Permission denied: Unable to write to /storage/app/uploads directory',
                'file_path' => '/app/Http/Controllers/FileUploadController.php',
                'line_number' => 89,
                'execution_time' => 234,
                'memory_usage' => 45 * 1024 * 1024,
                'context' => [
                    'attempted_file' => 'profile_photo_' . uniqid() . '.jpg',
                    'file_size' => '2.3MB',
                    'current_permissions' => '644',
                    'required_permissions' => '755'
                ]
            ],
            'memory-exhausted' => [
                'level' => 'critical',
                'message' => 'Fatal error: Allowed memory size of 128 MiB exhausted',
                'file_path' => '/app/Services/DataExportService.php',
                'line_number' => 203,
                'execution_time' => 12450,
                'memory_usage' => 128 * 1024 * 1024,
                'context' => [
                    'export_type' => 'user_analytics_report',
                    'records_processed' => 847293,
                    'total_records' => 1200000,
                    'memory_limit' => '128M',
                    'peak_memory' => '128M'
                ]
            ],
            'api-timeout' => [
                'level' => 'error',
                'message' => 'HTTP timeout: External API call to https://api.stripe.com exceeded 30 second limit',
                'file_path' => '/app/Services/PaymentGateway.php',
                'line_number' => 78,
                'execution_time' => 30000,
                'memory_usage' => 52 * 1024 * 1024,
                'context' => [
                    'api_endpoint' => 'https://api.stripe.com/v1/charges',
                    'timeout_limit' => '30s',
                    'retry_count' => 3,
                    'http_status' => null,
                    'payment_intent' => 'pi_' . uniqid()
                ]
            ],
        ];

        $scenarioData = $scenarios[$scenario] ?? $scenarios['database-connection'];
        
        return array_merge([
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'user_id' => 'usr_demo_' . uniqid(),
            'session_id' => 'sess_demo_' . uniqid(),
            'request_id' => 'req_demo_' . uniqid(),
            'ip_address' => '10.0.' . rand(1, 255) . '.' . rand(1, 255),
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'channel' => 'demo',
            'url' => config('app.url') . '/demo/' . $scenario,
            'method' => 'POST',
            'stack_trace' => $this->generateRealisticStackTrace($scenarioData['file_path'], $scenarioData['line_number']),
        ], $scenarioData);
    }
}