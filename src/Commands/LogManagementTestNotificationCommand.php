<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Fulgid\LogManagement\Facades\LogManagement;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Log;

class LogManagementTestNotificationCommand extends Command
{
    protected $signature = 'log-management:test-notification 
                            {--channel= : Test specific channel}
                            {--reset-stats : Reset notification statistics}';

    protected $description = 'Test notification system and check statistics';

    public function handle(): int
    {
        if ($this->option('reset-stats')) {
            $this->resetStats();
            return Command::SUCCESS;
        }

        $this->info('Testing notification system...');

        // Show current stats
        $this->showCurrentStats();

        // Test notification
        $channel = $this->option('channel');
        if ($channel) {
            $this->testSpecificChannel($channel);
        } else {
            $this->testAllChannels();
        }

        // Show updated stats
        $this->newLine();
        $this->info('Updated statistics:');
        $this->showCurrentStats();

        return Command::SUCCESS;
    }

    protected function showCurrentStats(): void
    {
        $stats = LogManagement::getStats();
        
        $this->table(['Metric', 'Value'], [
            ['Notifications Sent', $stats['notifications_sent'] ?? 0],
            ['Notifications Failed', $stats['notifications_failed'] ?? 0],
            ['Last Notification', $stats['last_notification'] ?? 'Never'],
        ]);

        // Show per-channel stats
        if (isset($stats['channels'])) {
            $this->newLine();
            $this->info('Per-Channel Statistics:');
            
            $channelData = [];
            foreach ($stats['channels'] as $channel => $channelStats) {
                $channelData[] = [
                    $channel,
                    $channelStats['sent'],
                    $channelStats['failed'],
                    $channelStats['last_notification'] ?? 'Never',
                ];
            }
            
            $this->table(['Channel', 'Sent', 'Failed', 'Last Notification'], $channelData);
        }

        // Show database counts
        $dbCounts = NotificationSetting::selectRaw('
            SUM(notification_count) as total_sent,
            SUM(failure_count) as total_failed,
            COUNT(*) as total_channels
        ')->first();

        $this->newLine();
        $this->info('Database Statistics:');
        $this->table(['Metric', 'Value'], [
            ['Total Sent (DB)', $dbCounts->total_sent ?? 0],
            ['Total Failed (DB)', $dbCounts->total_failed ?? 0],
            ['Configured Channels', $dbCounts->total_channels ?? 0],
        ]);
    }

    protected function testSpecificChannel(string $channel): void
    {
        $this->info("Testing {$channel} channel...");
        
        LogManagement::notify("Test notification for {$channel} channel", 'error', [
            'test' => true,
            'channel' => $channel,
            'timestamp' => now()->toISOString(),
        ]);

        $this->info("Test notification sent to {$channel} channel");
    }

    protected function testAllChannels(): void
    {
        $this->info('Testing all enabled channels...');
        
        LogManagement::notify('Test notification from command', 'error', [
            'test' => true,
            'command' => 'log-management:test-notification',
            'timestamp' => now()->toISOString(),
        ]);

        $this->info('Test notifications sent to all enabled channels');
    }

    protected function resetStats(): void
    {
        NotificationSetting::query()->update([
            'notification_count' => 0,
            'failure_count' => 0,
            'last_notification_at' => null,
            'last_failure_at' => null,
            'last_error' => null,
        ]);

        $this->info('Notification statistics reset successfully');
    }
}