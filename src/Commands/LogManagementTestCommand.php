<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Log;

class LogManagementTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'log-management:test 
                            {--channel= : Test specific notification channel}
                            {--all : Test all configured channels}
                            {--level=error : Log level for test notification}';

    /**
     * The console command description.
     */
    protected $description = 'Test the Log Management package configuration and notification channels';

    protected LogNotifierService $notifierService;

    /**
     * Create a new command instance.
     */
    public function __construct(LogNotifierService $notifierService)
    {
        parent::__construct();
        $this->notifierService = $notifierService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing Log Management Package Configuration...');
        $this->newLine();

        // Test basic configuration
        $this->testConfiguration();

        // Test database connectivity
        $this->testDatabase();

        // Test notification channels
        if ($this->option('channel')) {
            $this->testSpecificChannel($this->option('channel'));
        } elseif ($this->option('all')) {
            $this->testAllChannels();
        } else {
            $this->testEnabledChannels();
        }

        // Test log generation
        $this->testLogGeneration();

        $this->newLine();
        $this->info('✅ Log Management Package test completed!');

        return Command::SUCCESS;
    }

    /**
     * Test basic configuration.
     */
    protected function testConfiguration(): void
    {
        $this->comment('Testing Configuration...');

        $enabled = config('log-management.enabled', false);
        $this->line("Package enabled: " . ($enabled ? '✅ Yes' : '❌ No'));

        $notificationsEnabled = config('log-management.notifications.enabled', false);
        $this->line("Notifications enabled: " . ($notificationsEnabled ? '✅ Yes' : '❌ No'));

        $sseEnabled = config('log-management.sse.enabled', false);
        $this->line("SSE enabled: " . ($sseEnabled ? '✅ Yes' : '❌ No'));

        $databaseEnabled = config('log-management.database.enabled', false);
        $this->line("Database logging enabled: " . ($databaseEnabled ? '✅ Yes' : '❌ No'));

        $this->newLine();
    }

    /**
     * Test database connectivity.
     */
    protected function testDatabase(): void
    {
        $this->comment('Testing Database Connectivity...');

        try {
            \Fulgid\LogManagement\Models\LogEntry::count();
            $this->line("Database connection: ✅ OK");

            // Test notification settings table
            NotificationSetting::count();
            $this->line("Notification settings table: ✅ OK");
        } catch (\Exception $e) {
            $this->line("Database connection: ❌ Failed - " . $e->getMessage());
            $this->warn('Please run: php artisan migrate');
        }

        $this->newLine();
    }

    /**
     * Test specific notification channel.
     */
    protected function testSpecificChannel(string $channelName): void
    {
        $this->comment("Testing {$channelName} Channel...");

        $channels = $this->notifierService->getChannels();

        if (!isset($channels[$channelName])) {
            $this->error("Channel '{$channelName}' not found.");
            $this->line("Available channels: " . implode(', ', array_keys($channels)));
            return;
        }

        $channel = $channels[$channelName];
        $this->testChannel($channel);
    }

    /**
     * Test all configured channels.
     */
    protected function testAllChannels(): void
    {
        $this->comment('Testing All Notification Channels...');

        $channels = $this->notifierService->getChannels();

        if (empty($channels)) {
            $this->warn('No notification channels configured.');
            return;
        }

        foreach ($channels as $name => $channel) {
            $this->line("Testing {$name} channel:");
            $this->testChannel($channel);
            $this->newLine();
        }
    }

    /**
     * Test only enabled channels.
     */
    protected function testEnabledChannels(): void
    {
        $this->comment('Testing Enabled Notification Channels...');

        $channels = $this->notifierService->getChannels();
        $testedChannels = 0;

        foreach ($channels as $name => $channel) {
            if ($channel->isEnabled()) {
                $this->line("Testing {$name} channel:");
                $this->testChannel($channel);
                $this->newLine();
                $testedChannels++;
            }
        }

        if ($testedChannels === 0) {
            $this->warn('No enabled notification channels found.');
            $this->line('Configure channels in config/log-management.php or set environment variables.');
        }
    }

    /**
     * Test individual notification channel.
     */
    protected function testChannel($channel): void
    {
        $name = $channel->getName();

        // Test if channel is enabled
        $enabled = $channel->isEnabled();
        $this->line("  Enabled: " . ($enabled ? '✅ Yes' : '❌ No'));

        if (!$enabled) {
            return;
        }

        // Test configuration
        $configValid = $channel->validateConfiguration();
        $this->line("  Configuration: " . ($configValid ? '✅ Valid' : '❌ Invalid'));

        if (!$configValid) {
            $this->line("  Required config: " . implode(', ', $channel->getConfigurationRequirements()));
            return;
        }

        // Test connectivity
        if ($this->confirm("  Send test notification via {$name}?", false)) {
            $testResult = $channel->testConnection();
            $this->line("  Test result: " . ($testResult['success'] ? '✅ ' : '❌ ') . $testResult['message']);
        }
    }

    /**
     * Test log generation and processing.
     */
    protected function testLogGeneration(): void
    {
        $this->comment('Testing Log Generation...');

        if (!$this->confirm('Generate test log entries?', true)) {
            return;
        }

        $level = $this->option('level');
        $testMessage = 'Test log message from log-management:test command';

        try {
            // Generate test log
            Log::log($level, $testMessage, [
                'test' => true,
                'command' => 'log-management:test',
                'timestamp' => now()->toISOString(),
            ]);

            $this->line("Test log generated: ✅ {$level} level");

            // Check if log was stored in database
            if (config('log-management.database.enabled', true)) {
                $recentLog = \Fulgid\LogManagement\Models\LogEntry::where('message', $testMessage)
                    ->where('level', $level)
                    ->first();

                if ($recentLog) {
                    $this->line("Database storage: ✅ Log stored successfully");
                } else {
                    $this->line("Database storage: ⚠️  Log not found (may take a moment)");
                }
            }

            // Test notification sending
            if (config('log-management.notifications.enabled', true)) {
                $notificationLevels = config('log-management.notifications.levels', ['error', 'critical', 'emergency']);
                
                if (in_array($level, $notificationLevels)) {
                    $this->line("Notification trigger: ✅ Should trigger notifications");
                } else {
                    $this->line("Notification trigger: ℹ️  Level '{$level}' won't trigger notifications");
                    $this->line("Notification levels: " . implode(', ', $notificationLevels));
                }
            }

        } catch (\Exception $e) {
            $this->line("Test log generation: ❌ Failed - " . $e->getMessage());
        }
    }
}