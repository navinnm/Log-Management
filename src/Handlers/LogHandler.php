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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LogHandler extends AbstractProcessingHandler
{
    protected LogNotifierService $notifierService;
    protected LogStreamService $streamService;
    protected LogFilterService $filterService;
    protected static bool $processing = false; // Prevent infinite loops

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
        // CRITICAL: Prevent infinite logging loops
        if (self::$processing) {
            return;
        }

        // Skip if this is from our own package to prevent loops
        if (str_contains($record->message, 'log-management') || 
            str_contains($record->channel, 'log-management')) {
            return;
        }

        self::$processing = true;

        try {
            // Convert log record to array format with enhanced context
            $logData = $this->buildLogData($record);

            // Apply filters (with safe error handling)
            if (!$this->safeFilterCheck($logData)) {
                return;
            }

            // Store log entry in database if enabled
            if (config('log-management.database.enabled', true)) {
                $this->safeStoreLogEntry($logData);
            }

            // Check if we should send notifications
            if ($this->shouldNotify($record->level)) {
                $this->safeNotify($logData);
            }

            // Dispatch event for real-time streaming
            if (config('log-management.sse.enabled', true)) {
                $this->safeDispatchEvent($logData);
            }

        } catch (\Throwable $e) {
            // NEVER log errors from the log handler to prevent infinite loops
            // Instead, write directly to a separate error file if needed
            $this->writeErrorToFile($e, $record);
        } finally {
            self::$processing = false;
        }
    }

    /**
     * Safe filter check with error handling
     */
    protected function safeFilterCheck(array $logData): bool
    {
        try {
            return $this->filterService->shouldProcess($logData);
        } catch (\Throwable $e) {
            // If filtering fails, allow the log to proceed
            $this->writeErrorToFile($e, null, 'Filter check failed');
            return true;
        }
    }

    /**
     * Safe notification with error handling
     */
    protected function safeNotify(array $logData): void
    {
        try {
            $this->notifierService->notify(
                $logData['message'],
                $logData['level'],
                $logData['context']
            );
        } catch (\Throwable $e) {
            $this->writeErrorToFile($e, null, 'Notification failed');
        }
    }

    /**
     * Safe event dispatch with error handling
     */
    protected function safeDispatchEvent(array $logData): void
    {
        try {
            Event::dispatch(new LogEvent($logData));
        } catch (\Throwable $e) {
            $this->writeErrorToFile($e, null, 'Event dispatch failed');
        }
    }

    /**
     * Write error to a separate file to avoid logging loops
     */
    protected function writeErrorToFile(\Throwable $e, ?LogRecord $record = null, string $context = ''): void
    {
        try {
            $errorFile = storage_path('logs/log-management-errors.log');
            $timestamp = date('Y-m-d H:i:s');
            $errorMessage = "[$timestamp] LOG_MANAGEMENT_ERROR: $context - {$e->getMessage()}\n";
            
            // Use file_put_contents with LOCK_EX to safely write
            file_put_contents($errorFile, $errorMessage, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $writeError) {
            // If we can't even write to the error file, give up silently
            // to prevent any chance of infinite loops
        }
    }

    /**
     * Build comprehensive log data from the record.
     */
    protected function buildLogData(LogRecord $record): array
    {
        $request = $this->getCurrentRequest();
        $user = $this->getCurrentUser();
        
        return [
            'message' => $record->message,
            'level' => $record->level->name,
            'channel' => $record->channel,
            'datetime' => $record->datetime->format('Y-m-d H:i:s'),
            'context' => $this->processContext($record->context),
            'extra' => $this->processExtra($record->extra),
            'environment' => app()->environment(),
            'user_id' => $user?->id,
            'session_id' => $this->getSessionId(),
            'request_id' => $this->getRequestId($request),
            'ip_address' => $this->getIpAddress($request),
            'user_agent' => $this->getUserAgent($request),
            'url' => $this->getUrl($request),
            'method' => $this->getMethod($request),
            'status_code' => $this->getStatusCode($request),
            'execution_time' => $this->getExecutionTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'file_path' => $this->getFilePath($record),
            'line_number' => $this->getLineNumber($record),
            'stack_trace' => $this->getStackTrace($record),
            'tags' => $this->getTags($record),
        ];
    }

    /**
     * Store log entry in database with safe error handling.
     */
    protected function safeStoreLogEntry(array $logData): void
    {
        try {
            LogEntry::create([
                'message' => $logData['message'],
                'level' => strtolower($logData['level']),
                'channel' => $logData['channel'],
                'context' => json_encode($logData['context']),
                'extra' => json_encode($logData['extra']),
                'environment' => $logData['environment'],
                'user_id' => $logData['user_id'],
                'session_id' => $logData['session_id'],
                'request_id' => $logData['request_id'],
                'ip_address' => $logData['ip_address'],
                'user_agent' => $logData['user_agent'],
                'url' => $logData['url'],
                'method' => $logData['method'],
                'status_code' => $logData['status_code'],
                'execution_time' => $logData['execution_time'],
                'memory_usage' => $logData['memory_usage'],
                'file_path' => $logData['file_path'],
                'line_number' => $logData['line_number'],
                'stack_trace' => $logData['stack_trace'],
                'tags' => $logData['tags'] ? json_encode($logData['tags']) : null,
                'created_at' => $logData['datetime'],
            ]);
        } catch (\Throwable $e) {
            // NEVER use Log:: here - it would create an infinite loop
            $this->writeErrorToFile($e, null, 'Database storage failed');
        }
    }

    // Keep all your existing helper methods but remove any Log:: calls...
    
    /**
     * Get current request instance safely.
     */
    protected function getCurrentRequest()
    {
        try {
            if (app()->bound('request')) {
                return request();
            }
        } catch (\Throwable $e) {
            // Request not available (CLI, etc.)
        }
        
        return null;
    }

    /**
     * Get current authenticated user safely.
     */
    protected function getCurrentUser()
    {
        try {
            return Auth::user();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Process context data.
     */
    protected function processContext(array $context): array
    {
        // Add additional context if not present
        if (!isset($context['timestamp'])) {
            $context['timestamp'] = now()->toISOString();
        }

        // Add trace information if exception is present
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $exception = $context['exception'];
            $context['exception_class'] = get_class($exception);
            $context['exception_message'] = $exception->getMessage();
            $context['exception_file'] = $exception->getFile();
            $context['exception_line'] = $exception->getLine();
            $context['exception_trace'] = $exception->getTraceAsString();
        }

        return $context;
    }

    /**
     * Process extra data.
     */
    protected function processExtra(array $extra): array
    {
        // Add system information
        $extra['php_version'] = phpversion();
        $extra['laravel_version'] = app()->version();
        $extra['server_name'] = $_SERVER['SERVER_NAME'] ?? null;
        $extra['server_addr'] = $_SERVER['SERVER_ADDR'] ?? null;

        return $extra;
    }

    // ... (keep all your other helper methods like getSessionId, getRequestId, etc.)
    // Just remove any calls to Log:: facade from them

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

    // Add all your other helper methods here, but make sure NONE of them use Log:: facade
    // ... (getSessionId, getRequestId, getIpAddress, etc.)
    
    protected function getSessionId(): ?string
    {
        try {
            if (app()->bound('session') && session()->isStarted()) {
                return session()->getId();
            }
        } catch (\Exception $e) {
            // Session not available
        }
        
        return null;
    }

    protected function getRequestId($request): ?string
    {
        if (!$request) {
            return null;
        }

        $requestId = $request->header('X-Request-ID') ?? 
                    $request->header('X-Correlation-ID') ?? 
                    $request->header('Request-ID');

        if (!$requestId) {
            $requestId = (string) Str::uuid();
            $request->headers->set('X-Request-ID', $requestId);
        }

        return $requestId;
    }

    protected function getIpAddress($request): ?string
    {
        return $request?->ip();
    }

    protected function getUserAgent($request): ?string
    {
        return $request?->userAgent();
    }

    protected function getUrl($request): ?string
    {
        if (!$request) {
            return null;
        }

        try {
            return $request->fullUrl();
        } catch (\Exception $e) {
            return $request->url();
        }
    }

    protected function getMethod($request): ?string
    {
        return $request?->method();
    }

    protected function getStatusCode($request): ?int
    {
        try {
            if (app()->bound('response')) {
                $response = response();
                return $response->getStatusCode();
            }
        } catch (\Exception $e) {
            // Response not available
        }

        return null;
    }

    protected function getExecutionTime(): ?float
    {
        if (defined('LARAVEL_START')) {
            return round((microtime(true) - LARAVEL_START) * 1000, 2);
        }

        return null;
    }

    protected function getMemoryUsage(): ?int
    {
        return memory_get_usage(true);
    }

    protected function getFilePath(LogRecord $record): ?string
    {
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            return $record->context['exception']->getFile();
        }

        if (isset($record->extra['file'])) {
            return $record->extra['file'];
        }

        return null;
    }

    protected function getLineNumber(LogRecord $record): ?int
    {
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            return $record->context['exception']->getLine();
        }

        if (isset($record->extra['line'])) {
            return (int) $record->extra['line'];
        }

        return null;
    }

    protected function getStackTrace(LogRecord $record): ?string
    {
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            return $record->context['exception']->getTraceAsString();
        }

        return null;
    }

    protected function getTags(LogRecord $record): ?array
    {
        $tags = [];

        $tags[] = 'level:' . strtolower($record->level->name);
        $tags[] = 'channel:' . $record->channel;
        $tags[] = 'env:' . app()->environment();

        if (isset($record->context['tags']) && is_array($record->context['tags'])) {
            $tags = array_merge($tags, $record->context['tags']);
        }

        $user = $this->getCurrentUser();
        if ($user) {
            $tags[] = 'user_id:' . $user->id;
        }

        return empty($tags) ? null : $tags;
    }
}