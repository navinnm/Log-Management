<?php

namespace Fulgid\LogManagement\Tests\Feature;

use Fulgid\LogManagement\Tests\TestCase;
use Fulgid\LogManagement\Models\LogEntry;
use Fulgid\LogManagement\Models\NotificationSetting;
use Fulgid\LogManagement\Facades\LogManagement;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LogNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Http::fake();
    }

    public function test_error_log_triggers_email_notification(): void
    {
        // Create email notification setting
        $this->createTestNotificationSetting([
            'channel' => 'email',
            'enabled' => true,
            'settings' => [
                'to' => 'admin@example.com',
                'from' => 'noreply@example.com',
            ],
        ]);

        // Configure email channel
        config([
            'log-management.notifications.channels.email.enabled' => true,
            'log-management.notifications.channels.email.to' => 'admin@example.com',
        ]);

        // Trigger error log
        Log::error('Test error message', ['user_id' => 123]);

        // Wait a moment for async processing
        $this->artisan('queue:work', ['--once' => true]);

        // Assert log was stored
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test error message',
            'level' => 'error',
        ]);

        // Assert email was sent
        Mail::assertSent(\Fulgid\LogManagement\Notifications\LogNotification::class);
    }

    public function test_slack_notification_is_sent_for_critical_error(): void
    {
        // Create Slack notification setting
        $this->createTestNotificationSetting([
            'channel' => 'slack',
            'enabled' => true,
            'settings' => [
                'webhook_url' => 'https://hooks.slack.com/test',
                'channel' => '#alerts',
            ],
        ]);

        // Configure Slack channel
        config([
            'log-management.notifications.channels.slack.enabled' => true,
            'log-management.notifications.channels.slack.webhook_url' => 'https://hooks.slack.com/test',
        ]);

        // Mock successful Slack response
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        // Trigger critical log
        Log::critical('Critical system failure', ['system' => 'database']);

        // Assert HTTP request was made to Slack
        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/test' &&
                   str_contains($request->body(), 'Critical system failure');
        });
    }

    public function test_webhook_notification_includes_proper_payload(): void
    {
        // Create webhook notification setting
        $this->createTestNotificationSetting([
            'channel' => 'webhook',
            'enabled' => true,
            'settings' => [
                'url' => 'https://api.example.com/webhook',
                'method' => 'POST',
                'secret' => 'test-secret',
            ],
        ]);

        // Configure webhook channel
        config([
            'log-management.notifications.channels.webhook.enabled' => true,
            'log-management.notifications.channels.webhook.url' => 'https://api.example.com/webhook',
            'log-management.notifications.channels.webhook.secret' => 'test-secret',
        ]);

        // Mock successful webhook response
        Http::fake([
            'api.example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        // Trigger error log
        Log::error('Webhook test error', ['test_data' => 'value']);

        // Assert HTTP request was made with proper payload
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            
            return $request->url() === 'https://api.example.com/webhook' &&
                   $body['event'] === 'log.notification' &&
                   $body['log']['message'] === 'Webhook test error' &&
                   $body['log']['level'] === 'error' &&
                   isset($body['signature']);
        });
    }

    public function test_notification_respects_level_conditions(): void
    {
        // Create notification setting that only triggers for errors and above
        $this->createTestNotificationSetting([
            'channel' => 'email',
            'enabled' => true,
            'conditions' => [
                'levels' => ['error', 'critical', 'emergency'],
            ],
        ]);

        config([
            'log-management.notifications.channels.email.enabled' => true,
            'log-management.notifications.levels' => ['error', 'critical', 'emergency'],
        ]);

        // Trigger warning log (should not send notification)
        Log::warning('This is just a warning');

        // Trigger error log (should send notification)
        Log::error('This is an error');

        // Process any queued jobs
        $this->artisan('queue:work', ['--once' => true]);

        // Assert only one email was sent (for the error)
        Mail::assertSentCount(1);
    }

    public function test_notification_respects_environment_conditions(): void
    {
        // Create notification setting for production only
        $this->createTestNotificationSetting([
            'channel' => 'email',
            'enabled' => true,
            'conditions' => [
                'environments' => ['production'],
            ],
        ]);

        // Set environment to testing (should not trigger notification)
        app()->detectEnvironment(function () {
            return 'testing';
        });

        Log::error('Test error in testing environment');

        // Process any queued jobs
        $this->artisan('queue:work', ['--once' => true]);

        // Assert no email was sent
        Mail::assertNothingSent();
    }

    public function test_rate_limiting_prevents_spam(): void
    {
        // Create notification setting with rate limiting
        $setting = $this->createTestNotificationSetting([
            'channel' => 'email',
            'enabled' => true,
            'rate_limit' => [
                'enabled' => true,
                'max_per_minute' => 1,
                'window_minutes' => 1,
            ],
        ]);

        config([
            'log-management.notifications.channels.email.enabled' => true,
        ]);

        // Trigger multiple error logs quickly
        Log::error('First error');
        Log::error('Second error');
        Log::error('Third error');

        // Process queued jobs
        $this->artisan('queue:work', ['--once' => true]);

        // Assert only one email was sent due to rate limiting
        Mail::assertSentCount(1);
    }

    public function test_manual_notification_via_facade(): void
    {
        config([
            'log-management.notifications.channels.email.enabled' => true,
            'log-management.notifications.channels.email.to' => 'admin@example.com',
        ]);

        // Send manual notification
        // LogManagement::notify('Manual notification test', 'error', [
        //     'custom_data' => 'test_value',
        // ]);
        LogManagement::notify('Test notification from Log Management Package', 'error', ['test' => true]);

        // Process queued jobs
        $this->artisan('queue:work', ['--once' => true]);

        // Assert email was sent
        Mail::assertSent(\Fulgid\LogManagement\Notifications\LogNotification::class, function ($mail) {
            return $mail->logData['message'] === 'Manual notification test';
        });
    }

    public function test_notification_failure_is_handled_gracefully(): void
    {
        // Create webhook notification setting with invalid URL
        $this->createTestNotificationSetting([
            'channel' => 'webhook',
            'enabled' => true,
            'settings' => [
                'url' => 'https://invalid-webhook-url.example.com',
            ],
        ]);

        config([
            'log-management.notifications.channels.webhook.enabled' => true,
        ]);

        // Mock failed webhook response
        Http::fake([
            'invalid-webhook-url.example.com/*' => Http::response('Not Found', 404),
        ]);

        // Trigger error log
        Log::error('Test error for failed webhook');

        // Should not throw exception, should handle gracefully
        $this->assertTrue(true);
    }

    public function test_notification_includes_request_context(): void
    {
        config([
            'log-management.notifications.channels.email.enabled' => true,
        ]);

        // Simulate a web request
        $this->get('/some-url', [
            'User-Agent' => 'Test Browser',
            'X-Forwarded-For' => '192.168.1.1',
        ]);

        Log::error('Error with request context');

        // Process queued jobs
        $this->artisan('queue:work', ['--once' => true]);

        // Assert email was sent with request context
        Mail::assertSent(\Fulgid\LogManagement\Notifications\LogNotification::class, function ($mail) {
            return isset($mail->logData['url']) && isset($mail->logData['user_agent']);
        });
    }
}