<?php

namespace Fulgid\LogManagement\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Fulgid\LogManagement\LogManagementServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Load package migrations
        $this->loadLaravelMigrations();
        $this->artisan('migrate');
    }

    protected function getPackageProviders($app): array
    {
        return [
            LogManagementServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup test database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup log management configuration
        $app['config']->set('log-management.enabled', true);
        $app['config']->set('log-management.database.enabled', true);
        $app['config']->set('log-management.notifications.enabled', true);
        $app['config']->set('log-management.sse.enabled', true);
        $app['config']->set('log-management.auth.enabled', false);

        // Setup mail configuration for testing
        $app['config']->set('mail.default', 'array');
        
        // Setup cache for testing
        $app['config']->set('cache.default', 'array');
        
        // Setup queue for testing
        $app['config']->set('queue.default', 'sync');
    }

    /**
     * Define package aliases.
     */
    protected function getPackageAliases($app): array
    {
        return [
            'LogManagement' => \Fulgid\LogManagement\Facades\LogManagement::class,
        ];
    }

    /**
     * Create a test log entry.
     */
    protected function createTestLogEntry(array $attributes = []): \Fulgid\LogManagement\Models\LogEntry
    {
        return \Fulgid\LogManagement\Models\LogEntry::create(array_merge([
            'level' => 'error',
            'channel' => 'testing',
            'message' => 'Test log message',
            'context' => json_encode(['test' => true]),
            'extra' => json_encode([]),
            'created_at' => now(),
        ], $attributes));
    }

    /**
     * Create a test notification setting.
     */
    protected function createTestNotificationSetting(array $attributes = []): \Fulgid\LogManagement\Models\NotificationSetting
    {
        return \Fulgid\LogManagement\Models\NotificationSetting::create(array_merge([
            'channel' => 'email',
            'enabled' => true,
            'settings' => [
                'to' => 'test@example.com',
                'from' => 'noreply@example.com',
            ],
            'conditions' => [
                'levels' => ['error', 'critical'],
                'environments' => ['testing'],
            ],
        ], $attributes));
    }

    /**
     * Mock HTTP responses for external services.
     */
    protected function mockHttpResponses(array $responses = []): void
    {
        \Illuminate\Support\Facades\Http::fake($responses);
    }

    /**
     * Assert that a log entry exists in the database.
     */
    protected function assertLogEntryExists(string $message, string $level = 'error'): void
    {
        $this->assertDatabaseHas('log_entries', [
            'message' => $message,
            'level' => $level,
        ]);
    }

    /**
     * Assert that a notification was sent.
     */
    protected function assertNotificationSent(): void
    {
        // This would depend on how notifications are tracked
        // For now, we'll check if any notifications are in the mail array
        $this->assertTrue(count(\Illuminate\Support\Facades\Mail::queued()) > 0);
    }
}