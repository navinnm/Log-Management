<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Fulgid\LogManagement\Models\LogEntry;
use Illuminate\Support\Facades\Log;

class LogManagementDebugCommand extends Command
{
    protected $signature = 'log-management:debug 
                            {--generate-logs=10 : Generate test logs}
                            {--check-config : Check configuration}
                            {--test-sse : Test SSE endpoint}
                            {--check-db : Check database}';

    protected $description = 'Debug log management package issues';

    public function handle(): int
    {
        $this->info('🔍 Log Management Debug Tool');
        $this->newLine();

        if ($this->option('check-config')) {
            $this->checkConfiguration();
        }

        if ($this->option('check-db')) {
            $this->checkDatabase();
        }

        if ($this->option('generate-logs')) {
            $this->generateTestLogs((int) $this->option('generate-logs'));
        }

        if ($this->option('test-sse')) {
            $this->testSSEEndpoint();
        }

        if (!$this->hasOptions()) {
            $this->runAllChecks();
        }

        return Command::SUCCESS;
    }

    protected function hasOptions(): bool
    {
        return $this->option('check-config') || 
               $this->option('check-db') || 
               $this->option('generate-logs') || 
               $this->option('test-sse');
    }

    protected function runAllChecks(): void
    {
        $this->checkConfiguration();
        $this->newLine();
        $this->checkDatabase();
        $this->newLine();
        $this->testSSEEndpoint();
        $this->newLine();
        $this->generateTestLogs(5);
    }

    protected function checkConfiguration(): void
    {
        $this->comment('Configuration Check:');

        $checks = [
            'Package Enabled' => config('log-management.enabled', false),
            'SSE Enabled' => config('log-management.sse.enabled', false),
            'Database Enabled' => config('log-management.database.enabled', false),
            'Notifications Enabled' => config('log-management.notifications.enabled', false),
            'Auth Enabled' => config('log-management.auth.enabled', false),
        ];

        foreach ($checks as $name => $value) {
            $status = $value ? '✅ Enabled' : '❌ Disabled';
            $this->line("  {$name}: {$status}");
        }

        // Check API keys
        $apiKeys = config('log-management.auth.api_keys', []);
        $validKeys = array_filter($apiKeys);
        $this->line("  API Keys: " . count($validKeys) . " configured");

        // Check notification channels
        $channels = config('log-management.notifications.channels', []);
        $enabledChannels = array_filter($channels, fn($channel) => $channel['enabled'] ?? false);
        $this->line("  Notification Channels: " . count($enabledChannels) . " enabled");

        foreach ($enabledChannels as $name => $config) {
            $this->line("    - {$name}: ✅ Enabled");
        }
    }

    protected function checkDatabase(): void
    {
        $this->comment('Database Check:');

        try {
            // Check if tables exist
            $tables = ['log_entries', 'notification_settings'];
            foreach ($tables as $table) {
                if (\Schema::hasTable($table)) {
                    $count = \DB::table($table)->count();
                    $this->line("  ✅ Table '{$table}': {$count} records");
                } else {
                    $this->line("  ❌ Table '{$table}': Missing");
                }
            }

            // Check recent logs
            $recentLogs = LogEntry::where('created_at', '>=', now()->subHour())->count();
            $this->line("  📊 Recent logs (1h): {$recentLogs}");

            $totalLogs = LogEntry::count();
            $this->line("  📊 Total logs: {$totalLogs}");

            // Check log levels distribution
            $levels = LogEntry::selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray();

            if (!empty($levels)) {
                $this->line("  📊 Log levels:");
                foreach ($levels as $level => $count) {
                    $this->line("    - {$level}: {$count}");
                }
            }

        } catch (\Exception $e) {
            $this->error("  ❌ Database error: " . $e->getMessage());
        }
    }

    protected function testSSEEndpoint(): void
    {
        $this->comment('SSE Endpoint Check:');

        try {
            $url = url('/log-management/stream');
            $this->line("  🔗 SSE URL: {$url}");

            // Test if route exists
            $routes = \Route::getRoutes();
            $sseRoute = null;
            foreach ($routes as $route) {
                if (str_contains($route->uri(), 'log-management/stream')) {
                    $sseRoute = $route;
                    break;
                }
            }

            if ($sseRoute) {
                $this->line("  ✅ SSE Route: Found");
                $this->line("    Methods: " . implode(', ', $sseRoute->methods()));
                $this->line("    Middleware: " . implode(', ', $sseRoute->middleware()));
            } else {
                $this->line("  ❌ SSE Route: Not found");
            }

            // Check if we can make a basic request
            $apiKey = config('log-management.auth.api_keys')[0] ?? null;
            if ($apiKey) {
                $this->line("  🔑 Testing with API key: " . substr($apiKey, 0, 10) . '...');
                
                // Test with curl if available
                if (function_exists('curl_init')) {
                    $this->testSSEWithCurl($url, $apiKey);
                }
            } else {
                $this->line("  ⚠️  No API key configured for testing");
            }

        } catch (\Exception $e) {
            $this->error("  ❌ SSE test error: " . $e->getMessage());
        }
    }

    protected function testSSEWithCurl(string $url, string $apiKey): void
    {
        $fullUrl = $url . '?key=' . $apiKey;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: text/event-stream',
                'Cache-Control: no-cache',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->line("  ❌ cURL Error: {$error}");
            return;
        }

        $this->line("  📡 HTTP Response Code: {$httpCode}");

        if ($httpCode === 200) {
            $this->line("  ✅ SSE Endpoint: Accessible");
            
            // Check if response contains SSE headers
            if (str_contains($response, 'text/event-stream')) {
                $this->line("  ✅ Content-Type: text/event-stream");
            } else {
                $this->line("  ⚠️  Content-Type: Not SSE");
            }
        } elseif ($httpCode === 403) {
            $this->line("  ❌ SSE Endpoint: Access denied (check API key)");
        } elseif ($httpCode === 404) {
            $this->line("  ❌ SSE Endpoint: Not found (check routes)");
        } else {
            $this->line("  ❌ SSE Endpoint: Error (HTTP {$httpCode})");
        }
    }

    protected function generateTestLogs(int $count): void
    {
        $this->comment("Generating {$count} Test Logs:");

        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        $messages = [
            'emergency' => 'System is unusable',
            'alert' => 'Action must be taken immediately',
            'critical' => 'Critical conditions detected',
            'error' => 'Error conditions detected',
            'warning' => 'Warning conditions detected',
            'notice' => 'Normal but significant condition',
            'info' => 'Informational message',
            'debug' => 'Debug information'
        ];

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        for ($i = 0; $i < $count; $i++) {
            $level = $levels[array_rand($levels)];
            $message = $messages[$level] . " - Test log #{$i}";
            
            try {
                Log::log($level, $message, [
                    'test' => true,
                    'sequence' => $i,
                    'timestamp' => now()->toISOString(),
                    'generator' => 'log-management:debug',
                    'user_id' => rand(1, 100),
                    'session_id' => 'test_session_' . uniqid(),
                ]);
                
                $progressBar->advance();
                
                // Small delay to spread out the logs
                usleep(100000); // 0.1 seconds
                
            } catch (\Exception $e) {
                $progressBar->advance();
                $this->newLine();
                $this->error("Failed to generate log {$i}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Generated {$count} test logs");

        // Show recent count
        $recent = LogEntry::where('created_at', '>=', now()->subMinute())->count();
        $this->line("📊 Logs in last minute: {$recent}");
    }
}

// Add this to your service provider commands array:
// LogManagementDebugCommand::class,