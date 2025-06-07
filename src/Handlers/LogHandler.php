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
use Illuminate\Support\Facades\Log;

class LogHandler extends AbstractProcessingHandler
{
    protected LogNotifierService $notifierService;
    protected LogStreamService $streamService;
    protected LogFilterService $filterService;
    
    /**
     * Prevent infinite logging loops - static to persist across instances
     */
    private static bool $isProcessing = false;
    private static array $processedHashes = [];
    private static int $maxProcessedHashes = 100;

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
        // Critical: Prevent infinite loops
        if (self::$isProcessing) {
            return;
        }

        // Skip processing our own log management related logs
        if ($this->isLogManagementInternalLog($record)) {
            return;
        }

        // Check for duplicate processing using content hash
        $recordHash = $this->generateRecordHash($record);
        if ($this->isDuplicateRecord($recordHash)) {
            return;
        }

        // Set processing flag to prevent recursion
        self::$isProcessing = true;

        try {
            $this->processLogRecord($record, $recordHash);
        } catch (\Exception $e) {
            // Log to system error log to prevent infinite loops
            error_log("Log Management Handler Error: " . $e->getMessage());
            
            // If this is a critical error in production, try to send a simple notification
            if (app()->environment('production') && $this->isCriticalError($e)) {
                $this->sendEmergencyNotification($e, $record);
            }
        } finally {
            // Always reset the processing flag
            self::$isProcessing = false;
        }
    }

    /**
     * Process the log record safely
     */
    protected function processLogRecord(LogRecord $record, string $recordHash): void
    {
        // Convert log record to array format
        $logData = $this->convertRecordToArray($record);

        // Add hash to processed list to prevent duplicates
        $this->addToProcessedHashes($recordHash);

        // Apply filters - use try-catch to prevent filter errors from breaking the flow
        try {
            if (!$this->filterService->shouldProcess($logData)) {
                return;
            }
        } catch (\Exception $e) {
            error_log("Log Filter Service Error: " . $e->getMessage());
            // Continue processing even if filter fails
        }

        // Store log entry in database if enabled
        if (config('log-management.database.enabled', true)) {
            $this->storeLogEntry($logData);
        }

        // Check if we should send notifications
        if ($this->shouldNotify($record->level)) {
            $this->sendNotification($logData);
        }

        // Dispatch event for real-time streaming
        if (config('log-management.sse.enabled', true)) {
            $this->dispatchStreamEvent($logData);
        }
    }

    /**
     * Convert LogRecord to array format
     */
    protected function convertRecordToArray(LogRecord $record): array
    {
        return [
            'message' => $record->message,
            'level' => $record->level->name,
            'channel' => $record->channel,
            'datetime' => $record->datetime->format('Y-m-d H:i:s'),
            'context' => $record->context,
            'extra' => $record->extra,
            'environment' => config('app.env'),
            'timestamp' => $record->datetime->format('c'), // ISO 8601 format
        ];
    }

    /**
     * Check if this is a log management internal log to prevent loops
     */
    protected function isLogManagementInternalLog(LogRecord $record): bool
    {
        // Check context for internal flag
        if (isset($record->context['log_management_internal']) && $record->context['log_management_internal'] === true) {
            return true;
        }

        // Check if the message comes from log management classes
        $logManagementPatterns = [
            'EmailChannel:',
            'LogManagement:',
            'Log Management',
            'Fulgid\\LogManagement',
        ];

        foreach ($logManagementPatterns as $pattern) {
            if (strpos($record->message, $pattern) !== false) {
                return true;
            }
        }

        // Check channel
        if ($record->channel === 'log-management' || $record->channel === 'log_management') {
            return true;
        }

        return false;
    }

    /**
     * Generate a hash for the record to detect duplicates
     */
    protected function generateRecordHash(LogRecord $record): string
    {
        $hashData = [
            'message' => $record->message,
            'level' => $record->level->name,
            'channel' => $record->channel,
            'minute' => $record->datetime->format('Y-m-d-H-i'), // Group by minute
        ];

        return md5(serialize($hashData));
    }

    /**
     * Check if this record has been processed recently
     */
    protected function isDuplicateRecord(string $hash): bool
    {
        return in_array($hash, self::$processedHashes);
    }

    /**
     * Add hash to processed list and maintain size limit
     */
    protected function addToProcessedHashes(string $hash): void
    {
        self::$processedHashes[] = $hash;

        // Keep only the most recent hashes to prevent memory issues
        if (count(self::$processedHashes) > self::$maxProcessedHashes) {
            self::$processedHashes = array_slice(self::$processedHashes, -self::$maxProcessedHashes);
        }
    }

    /**
     * Store log entry in database with error handling
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
            // Log to system error log to prevent database issues from causing loops
            error_log("Log Management Database Error: " . $e->getMessage());
        }
    }

    /**
     * Send notification with error handling
     */
    protected function sendNotification(array $logData): void
    {
        try {
            $this->notifierService->notify(
                $logData['message'],
                $logData['level'],
                $logData['context']
            );
        } catch (\Exception $e) {
            // Log notification errors to system log
            error_log("Log Management Notification Error: " . $e->getMessage());
        }
    }

    /**
     * Dispatch stream event with error handling
     */
    protected function dispatchStreamEvent(array $logData): void
    {
        try {
            Event::dispatch(new LogEvent($logData));
        } catch (\Exception $e) {
            // Log streaming errors to system log
            error_log("Log Management Stream Error: " . $e->getMessage());
        }
    }

    /**
     * Determine if we should send notifications for this log level
     */
    protected function shouldNotify(Level $level): bool
    {
        if (!config('log-management.notifications.enabled', true)) {
            return false;
        }

        $notificationLevels = config('log-management.notifications.levels', ['error', 'critical', 'emergency']);
        
        return in_array(strtolower($level->name), $notificationLevels);
    }

    /**
     * Check if this is a critical error that requires immediate attention
     */
    protected function isCriticalError(\Exception $e): bool
    {
        $criticalErrors = [
            'file_put_contents',
            'Failed to open stream',
            'Permission denied',
            'No such file or directory',
        ];

        foreach ($criticalErrors as $error) {
            if (strpos($e->getMessage(), $error) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send emergency notification using system methods when regular notifications fail
     */
    protected function sendEmergencyNotification(\Exception $e, LogRecord $record): void
    {
        try {
            // Try to send a simple email using basic PHP mail function
            $to = config('log-management.notifications.email.emergency_contact', config('mail.from.address'));
            $subject = '[CRITICAL] Log Management System Error';
            $message = "Critical error in Log Management System:\n\n";
            $message .= "Error: " . $e->getMessage() . "\n";
            $message .= "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            $message .= "Original Log: " . $record->message . "\n";
            $message .= "Level: " . $record->level->name . "\n";
            $message .= "Time: " . $record->datetime->format('Y-m-d H:i:s') . "\n";
            $message .= "Environment: " . config('app.env') . "\n\n";
            $message .= "Please check the server immediately.";

            $headers = 'From: ' . config('mail.from.address');
            
            // Use basic PHP mail function as last resort
            if (function_exists('mail')) {
                mail($to, $subject, $message, $headers);
            }
        } catch (\Exception $mailException) {
            // If even basic mail fails, log to system
            error_log("Emergency notification failed: " . $mailException->getMessage());
        }
    }

    /**
     * Clear processed hashes (useful for testing or memory management)
     */
    public static function clearProcessedHashes(): void
    {
        self::$processedHashes = [];
    }

    /**
     * Get current processing status (useful for debugging)
     */
    public static function isCurrentlyProcessing(): bool
    {
        return self::$isProcessing;
    }

    /**
     * Force reset processing flag (emergency use only)
     */
    public static function forceResetProcessingFlag(): void
    {
        self::$isProcessing = false;
    }
}