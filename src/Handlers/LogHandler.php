<?php

namespace Fulgid\LogManagement\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Level;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Services\LogFilterService;
use Fulgid\LogManagement\Models\LogEntry;
use Fulgid\LogManagement\Events\LogEvent;
use Illuminate\Support\Facades\Event;

class LogHandler extends AbstractProcessingHandler
{
    protected LogNotifierService $notifierService;
    protected LogStreamService $streamService;
    protected LogFilterService $filterService;

    public function __construct(
        LogNotifierService $notifierService,
        LogStreamService $streamService,
        LogFilterService $filterService,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->notifierService = $notifierService;
        $this->streamService = $streamService;
        $this->filterService = $filterService;
    }

    /**
     * Writes the record down to the log of the implementing handler
     */
    protected function write(LogRecord $record): void
    {
        // Convert log record to array format
        $logData = [
            'message' => $record->message,
            'level' => $record->level->name,
            'channel' => $record->channel,
            'datetime' => $record->datetime->format('Y-m-d H:i:s'),
            'context' => $record->context,
            'extra' => $record->extra,
        ];

        // Apply filters
        if (!$this->filterService->shouldProcess($logData)) {
            return;
        }

        // Store log entry in database if enabled
        if (config('log-management.database.enabled', true)) {
            $this->storeLogEntry($logData);
        }

        // Check if we should send notifications
        if ($this->shouldNotify($record->level)) {
            $this->notifierService->notify(
                $logData['message'],
                $logData['level'],
                $logData['context']
            );
        }

        // Dispatch event for real-time streaming
        if (config('log-management.sse.enabled', true)) {
            Event::dispatch(new LogEvent($logData));
        }
    }

    /**
     * Store log entry in database.
     */
    protected function storeLogEntry(array $logData): void
    {
        try {
            LogEntry::create([
                'message' => $logData['message'],
                'level' => strtolower($logData['level']),
                'channel' => $logData['channel'],
                'context' => json_encode($logData['context']),
                'extra' => json_encode($logData['extra']),
                'created_at' => $logData['datetime'],
            ]);
        } catch (\Exception $e) {
            // Silently fail to prevent logging loops
        }
    }

    /**
     * Determine if we should send notifications for this log level.
     */
    protected function shouldNotify(Level $level): bool
    {
        if (!config('log-management.notifications.enabled', true)) {
            return false;
        }

        $notificationLevels = config('log-management.notifications.levels', ['error', 'critical', 'emergency']);
        
        return in_array(strtolower($level->name), $notificationLevels);
    }
}