<?php

namespace Fulgid\LogManagement\Tests\Unit;

use Fulgid\LogManagement\Tests\TestCase;
use Fulgid\LogManagement\Handlers\LogHandler;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Services\LogFilterService;
use Fulgid\LogManagement\Models\LogEntry;
use Fulgid\LogManagement\Events\LogEvent;
use Illuminate\Support\Facades\Event;
use Monolog\LogRecord;
use Monolog\Level;

class LogHandlerTest extends TestCase
{
    protected LogHandler $handler;
    protected LogNotifierService $notifierService;
    protected LogStreamService $streamService;
    protected LogFilterService $filterService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifierService = $this->createMock(LogNotifierService::class);
        $this->streamService = $this->createMock(LogStreamService::class);
        $this->filterService = $this->createMock(LogFilterService::class);

        $this->handler = new LogHandler(
            $this->notifierService,
            $this->streamService,
            $this->filterService
        );

        Event::fake();
    }

    public function test_log_handler_processes_log_record(): void
    {
        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: ['user_id' => 123],
            extra: []
        );

        $this->handler->handle($logRecord);

        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test error message',
            'level' => 'error',
            'channel' => 'testing',
        ]);
    }

    public function test_log_handler_triggers_notification_for_error_level(): void
    {
        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $this->notifierService
            ->expects($this->once())
            ->method('notify')
            ->with(
                $this->equalTo('Test error message'),
                $this->equalTo('ERROR'),
                $this->equalTo(['user_id' => 123])
            );

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: ['user_id' => 123],
            extra: []
        );

        $this->handler->handle($logRecord);
    }

    public function test_log_handler_does_not_trigger_notification_for_info_level(): void
    {
        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $this->notifierService
            ->expects($this->never())
            ->method('notify');

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Info,
            message: 'Test info message',
            context: [],
            extra: []
        );

        $this->handler->handle($logRecord);
    }

    public function test_log_handler_dispatches_log_event(): void
    {
        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: ['user_id' => 123],
            extra: []
        );

        $this->handler->handle($logRecord);

        Event::assertDispatched(LogEvent::class, function ($event) {
            return $event->logData['message'] === 'Test error message' &&
                   $event->logData['level'] === 'ERROR';
        });
    }

    public function test_log_handler_skips_processing_when_filter_returns_false(): void
    {
        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(false);

        $this->notifierService
            ->expects($this->never())
            ->method('notify');

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: [],
            extra: []
        );

        $this->handler->handle($logRecord);

        $this->assertDatabaseMissing('log_entries', [
            'message' => 'Test error message',
        ]);

        Event::assertNotDispatched(LogEvent::class);
    }

    public function test_log_handler_handles_database_errors_gracefully(): void
    {
        // Simulate database error by using invalid data
        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        // Mock the LogEntry to throw an exception
        $this->mock(LogEntry::class, function ($mock) {
            $mock->shouldReceive('create')->andThrow(new \Exception('Database error'));
        });

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: [],
            extra: []
        );

        // Should not throw an exception
        $this->handler->handle($logRecord);

        // Event should still be dispatched even if database storage fails
        Event::assertDispatched(LogEvent::class);
    }

    public function test_log_handler_respects_notification_level_configuration(): void
    {
        // Test with warning level when warnings are not in notification levels
        config(['log-management.notifications.levels' => ['error', 'critical']]);

        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $this->notifierService
            ->expects($this->never())
            ->method('notify');

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Warning,
            message: 'Test warning message',
            context: [],
            extra: []
        );

        $this->handler->handle($logRecord);
    }

    public function test_log_handler_respects_disabled_notifications(): void
    {
        config(['log-management.notifications.enabled' => false]);

        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $this->notifierService
            ->expects($this->never())
            ->method('notify');

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: [],
            extra: []
        );

        $this->handler->handle($logRecord);

        // Should still store in database and dispatch event
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test error message',
        ]);

        Event::assertDispatched(LogEvent::class);
    }

    public function test_log_handler_respects_disabled_sse(): void
    {
        config(['log-management.sse.enabled' => false]);

        $this->filterService
            ->expects($this->once())
            ->method('shouldProcess')
            ->willReturn(true);

        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'testing',
            level: Level::Error,
            message: 'Test error message',
            context: [],
            extra: []
        );

        $this->handler->handle($logRecord);

        // Should still store in database but not dispatch event
        $this->assertDatabaseHas('log_entries', [
            'message' => 'Test error message',
        ]);

        Event::assertNotDispatched(LogEvent::class);
    }
}