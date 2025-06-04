<?php

namespace Fulgid\LogManagement\Tests\Unit;

use Fulgid\LogManagement\Tests\TestCase;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Log;

class LogNotifierServiceTest extends TestCase
{
    protected LogNotifierService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LogNotifierService();
    }

    public function test_can_add_notification_channel(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('getName')->willReturn('test');

        $this->service->addChannel('test', $channel);

        $channels = $this->service->getChannels();
        $this->assertArrayHasKey('test', $channels);
        $this->assertSame($channel, $channels['test']);
    }

    public function test_can_remove_notification_channel(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('getName')->willReturn('test');

        $this->service->addChannel('test', $channel);
        $this->service->removeChannel('test');

        $channels = $this->service->getChannels();
        $this->assertArrayNotHasKey('test', $channels);
    }

    public function test_notify_sends_to_enabled_channels(): void
    {
        $enabledChannel = $this->createMock(NotificationChannelInterface::class);
        $enabledChannel->method('isEnabled')->willReturn(true);
        $enabledChannel->expects($this->once())->method('send')->willReturn(true);

        $disabledChannel = $this->createMock(NotificationChannelInterface::class);
        $disabledChannel->method('isEnabled')->willReturn(false);
        $disabledChannel->expects($this->never())->method('send');

        $this->service->addChannel('enabled', $enabledChannel);
        $this->service->addChannel('disabled', $disabledChannel);

        $this->service->notify('Test message', 'error', ['test' => true]);
    }

    public function test_notify_updates_stats_on_success(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->method('send')->willReturn(true);

        $this->service->addChannel('test', $channel);

        $initialStats = $this->service->getStats();
        $this->service->notify('Test message', 'error');

        $updatedStats = $this->service->getStats();
        $this->assertEquals($initialStats['notifications_sent'] + 1, $updatedStats['notifications_sent']);
        $this->assertNotNull($updatedStats['last_notification']);
    }

    public function test_notify_updates_stats_on_failure(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->method('send')->willReturn(false);

        $this->service->addChannel('test', $channel);

        $initialStats = $this->service->getStats();
        $this->service->notify('Test message', 'error');

        $updatedStats = $this->service->getStats();
        $this->assertEquals($initialStats['notifications_failed'] + 1, $updatedStats['notifications_failed']);
    }

    public function test_notify_handles_channel_exceptions(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->method('send')->willThrowException(new \Exception('Channel error'));

        $this->service->addChannel('test', $channel);

        // Should not throw exception
        $this->service->notify('Test message', 'error');

        $stats = $this->service->getStats();
        $this->assertEquals(1, $stats['notifications_failed']);
    }

    public function test_notify_includes_all_log_data(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($logData) {
                return $logData['message'] === 'Test message' &&
                       $logData['level'] === 'error' &&
                       $logData['context']['test'] === true &&
                       isset($logData['timestamp']) &&
                       isset($logData['environment']);
            }))
            ->willReturn(true);

        $this->service->addChannel('test', $channel);

        $this->service->notify('Test message', 'error', ['test' => true]);
    }

    public function test_is_enabled_returns_correct_status(): void
    {
        // Test when enabled
        config(['log-management.enabled' => true, 'log-management.notifications.enabled' => true]);
        $this->assertTrue($this->service->isEnabled());

        // Test when package disabled
        config(['log-management.enabled' => false, 'log-management.notifications.enabled' => true]);
        $this->assertFalse($this->service->isEnabled());

        // Test when notifications disabled
        config(['log-management.enabled' => true, 'log-management.notifications.enabled' => false]);
        $this->assertFalse($this->service->isEnabled());
    }

    public function test_get_stats_returns_expected_structure(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('notifications_sent', $stats);
        $this->assertArrayHasKey('notifications_failed', $stats);
        $this->assertArrayHasKey('last_notification', $stats);
        $this->assertIsInt($stats['notifications_sent']);
        $this->assertIsInt($stats['notifications_failed']);
    }

    public function test_notify_with_request_context(): void
    {
        $this->app['request']->merge(['test' => 'value']);
        $this->app['request']->server->set('HTTP_USER_AGENT', 'TestAgent');
        $this->app['request']->server->set('REMOTE_ADDR', '127.0.0.1');

        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($logData) {
                return isset($logData['user_agent']) &&
                       isset($logData['ip']);
            }))
            ->willReturn(true);

        $this->service->addChannel('test', $channel);

        $this->service->notify('Test message', 'error');
    }
}