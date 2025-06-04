<?php

namespace Fulgid\LogManagement\Tests\Feature;

use Fulgid\LogManagement\Tests\TestCase;
use Fulgid\LogManagement\Events\LogEvent;
use Fulgid\LogManagement\Models\LogEntry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class LogStreamTest extends TestCase
{
    public function test_log_stream_endpoint_returns_sse_response(): void
    {
        $response = $this->get(route('log-management.stream'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
        $response->assertHeader('Cache-Control', 'no-cache');
        $response->assertHeader('Connection', 'keep-alive');
    }

    public function test_log_stream_endpoint_requires_authentication_when_enabled(): void
    {
        config(['log-management.auth.enabled' => true]);

        $response = $this->get(route('log-management.stream'));

        $response->assertStatus(403);
    }

    public function test_log_stream_endpoint_accepts_api_key(): void
    {
        config([
            'log-management.auth.enabled' => true,
            'log-management.auth.api_keys' => ['test-api-key'],
        ]);

        $response = $this->get(route('log-management.stream'), [
            'X-Log-Management-Key' => 'test-api-key',
        ]);

        $response->assertStatus(200);
    }

    public function test_log_event_is_dispatched_when_log_is_created(): void
    {
        Event::fake();

        Log::error('Test error message', ['user_id' => 123]);

        Event::assertDispatched(LogEvent::class, function ($event) {
            return $event->logData['message'] === 'Test error message' &&
                   $event->logData['level'] === 'ERROR';
        });
    }

    public function test_log_api_returns_paginated_logs(): void
    {
        // Create test log entries
        $this->createTestLogEntry(['message' => 'First log', 'level' => 'error']);
        $this->createTestLogEntry(['message' => 'Second log', 'level' => 'warning']);
        $this->createTestLogEntry(['message' => 'Third log', 'level' => 'info']);

        $response = $this->getJson(route('log-management.api.logs.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'level',
                    'message',
                    'channel',
                    'created_at',
                ]
            ],
            'pagination' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]
        ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_log_api_filters_by_level(): void
    {
        // Create test log entries with different levels
        $this->createTestLogEntry(['message' => 'Error log', 'level' => 'error']);
        $this->createTestLogEntry(['message' => 'Warning log', 'level' => 'warning']);
        $this->createTestLogEntry(['message' => 'Info log', 'level' => 'info']);

        $response = $this->getJson(route('log-management.api.logs.index', ['level' => 'error']));

        $response->assertStatus(200);
        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals('error', $logs[0]['level']);
        $this->assertEquals('Error log', $logs[0]['message']);
    }

    public function test_log_api_filters_by_multiple_levels(): void
    {
        // Create test log entries
        $this->createTestLogEntry(['level' => 'error']);
        $this->createTestLogEntry(['level' => 'warning']);
        $this->createTestLogEntry(['level' => 'info']);
        $this->createTestLogEntry(['level' => 'debug']);

        $response = $this->getJson(route('log-management.api.logs.index', [
            'level' => ['error', 'warning']
        ]));

        $response->assertStatus(200);
        $logs = $response->json('data');
        $this->assertCount(2, $logs);
    }

    public function test_log_api_filters_by_channel(): void
    {
        // Create test log entries with different channels
        $this->createTestLogEntry(['channel' => 'application']);
        $this->createTestLogEntry(['channel' => 'database']);
        $this->createTestLogEntry(['channel' => 'queue']);

        $response = $this->getJson(route('log-management.api.logs.index', ['channel' => 'database']));

        $response->assertStatus(200);
        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals('database', $logs[0]['channel']);
    }

    public function test_log_api_searches_in_message(): void
    {
        // Create test log entries
        $this->createTestLogEntry(['message' => 'Database connection failed']);
        $this->createTestLogEntry(['message' => 'User authentication error']);
        $this->createTestLogEntry(['message' => 'File upload successful']);

        $response = $this->getJson(route('log-management.api.logs.index', ['search' => 'database']));

        $response->assertStatus(200);
        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('Database', $logs[0]['message']);
    }

    public function test_log_api_filters_by_date_range(): void
    {
        // Create log entries with different dates
        $this->createTestLogEntry([
            'message' => 'Old log',
            'created_at' => now()->subDays(5),
        ]);
        $this->createTestLogEntry([
            'message' => 'Recent log',
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->getJson(route('log-management.api.logs.index', [
            'from' => now()->subDays(1)->toDateString(),
            'to' => now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $logs = $response->json('data');
        $this->assertCount(1, $logs);
        $this->assertEquals('Recent log', $logs[0]['message']);
    }

    public function test_stats_endpoint_returns_correct_structure(): void
    {
        // Create some test data
        $this->createTestLogEntry(['level' => 'error']);
        $this->createTestLogEntry(['level' => 'warning']);
        $this->createTestLogEntry(['level' => 'info']);

        $response = $this->getJson(route('log-management.stats'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'stream_stats',
            'log_counts' => [
                'total',
                'today',
                'last_hour',
            ],
            'level_breakdown',
        ]);
    }

    public function test_health_endpoint_returns_service_status(): void
    {
        $response = $this->getJson(route('log-management.health'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'services' => [
                'database',
                'sse',
                'notifications',
            ],
        ]);
    }

    public function test_log_stream_filters_by_level_parameter(): void
    {
        $response = $this->get(route('log-management.stream', ['level' => 'error']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/event-stream');
    }

    public function test_log_stream_includes_recent_logs_when_requested(): void
    {
        // Create some recent log entries
        $this->createTestLogEntry(['message' => 'Recent error']);
        $this->createTestLogEntry(['message' => 'Recent warning']);

        $response = $this->get(route('log-management.stream', ['include_recent' => 'true']));

        $response->assertStatus(200);
    }

    public function test_log_entry_model_scopes_work_correctly(): void
    {
        // Test data
        $this->createTestLogEntry(['level' => 'error', 'created_at' => now()]);
        $this->createTestLogEntry(['level' => 'warning', 'created_at' => now()->subHour()]);
        $this->createTestLogEntry(['level' => 'info', 'created_at' => now()->subDays(2)]);

        // Test level scope
        $errorLogs = LogEntry::level('error')->get();
        $this->assertCount(1, $errorLogs);

        // Test today scope
        $todayLogs = LogEntry::today()->get();
        $this->assertCount(2, $todayLogs);

        // Test last hour scope
        $lastHourLogs = LogEntry::lastHour()->get();
        $this->assertCount(1, $lastHourLogs);

        // Test errors scope
        $errorLogs = LogEntry::errors()->get();
        $this->assertCount(1, $errorLogs);
    }
}