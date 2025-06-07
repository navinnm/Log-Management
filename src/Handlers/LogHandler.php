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
        // Convert log record to array format with enhanced context
        $logData = $this->buildLogData($record);

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
     * Get current request instance safely.
     */
    protected function getCurrentRequest()
    {
        try {
            if (app()->bound('request')) {
                return request();
            }
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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

    /**
     * Get session ID.
     */
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

    /**
     * Get request ID.
     */
    protected function getRequestId($request): ?string
    {
        if (!$request) {
            return null;
        }

        // Try to get from header first
        $requestId = $request->header('X-Request-ID') ?? 
                    $request->header('X-Correlation-ID') ?? 
                    $request->header('Request-ID');

        // Generate one if not present
        if (!$requestId) {
            $requestId = (string) Str::uuid();
            // Store it for this request
            $request->headers->set('X-Request-ID', $requestId);
        }

        return $requestId;
    }

    /**
     * Get IP address.
     */
    protected function getIpAddress($request): ?string
    {
        if (!$request) {
            return null;
        }

        return $request->ip();
    }

    /**
     * Get user agent.
     */
    protected function getUserAgent($request): ?string
    {
        if (!$request) {
            return null;
        }

        return $request->userAgent();
    }

    /**
     * Get URL.
     */
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

    /**
     * Get HTTP method.
     */
    protected function getMethod($request): ?string
    {
        if (!$request) {
            return null;
        }

        return $request->method();
    }

    /**
     * Get status code (if response is available).
     */
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

    /**
     * Get execution time.
     */
    protected function getExecutionTime(): ?float
    {
        if (defined('LARAVEL_START')) {
            return round((microtime(true) - LARAVEL_START) * 1000, 2); // in milliseconds
        }

        return null;
    }

    /**
     * Get memory usage.
     */
    protected function getMemoryUsage(): ?int
    {
        return memory_get_usage(true);
    }

    /**
     * Get file path from log record.
     */
    protected function getFilePath(LogRecord $record): ?string
    {
        // Try to extract from context first
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            return $record->context['exception']->getFile();
        }

        // Try to get from extra data
        if (isset($record->extra['file'])) {
            return $record->extra['file'];
        }

        // Get from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $item) {
            if (isset($item['file']) && !str_contains($item['file'], 'vendor/monolog')) {
                return $item['file'];
            }
        }

        return null;
    }

    /**
     * Get line number from log record.
     */
    protected function getLineNumber(LogRecord $record): ?int
    {
        // Try to extract from context first
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            return $record->context['exception']->getLine();
        }

        // Try to get from extra data
        if (isset($record->extra['line'])) {
            return (int) $record->extra['line'];
        }

        // Get from backtrace
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($trace as $item) {
            if (isset($item['file']) && !str_contains($item['file'], 'vendor/monolog')) {
                return $item['line'] ?? null;
            }
        }

        return null;
    }

    /**
     * Get stack trace.
     */
    protected function getStackTrace(LogRecord $record): ?string
    {
        // Try to extract from context first
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            return $record->context['exception']->getTraceAsString();
        }

        // Generate stack trace for error level logs
        if (in_array(strtolower($record->level->name), ['error', 'critical', 'emergency', 'alert'])) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $formattedTrace = [];
            
            foreach ($trace as $index => $item) {
                $file = $item['file'] ?? 'unknown';
                $line = $item['line'] ?? 'unknown';
                $function = $item['function'] ?? 'unknown';
                $class = isset($item['class']) ? $item['class'] . '::' : '';
                
                $formattedTrace[] = "#{$index} {$file}({$line}): {$class}{$function}()";
            }
            
            return implode("\n", $formattedTrace);
        }

        return null;
    }

    /**
     * Get tags from log record.
     */
    protected function getTags(LogRecord $record): ?array
    {
        $tags = [];

        // Add level as tag
        $tags[] = 'level:' . strtolower($record->level->name);

        // Add channel as tag
        $tags[] = 'channel:' . $record->channel;

        // Add environment as tag
        $tags[] = 'env:' . app()->environment();

        // Extract tags from context
        if (isset($record->context['tags']) && is_array($record->context['tags'])) {
            $tags = array_merge($tags, $record->context['tags']);
        }

        // Add user-related tags if available
        $user = $this->getCurrentUser();
        if ($user) {
            $tags[] = 'user_id:' . $user->id;
            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames();
                foreach ($roles as $role) {
                    $tags[] = 'role:' . $role;
                }
            }
        }

        return empty($tags) ? null : $tags;
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
        } catch (\Exception $e) {
            // Silently fail to prevent logging loops
            // Optionally log to a different channel
            try {
                \Illuminate\Support\Facades\Log::channel('single')->error(
                    'Failed to store log entry: ' . $e->getMessage(),
                    ['original_log' => $logData]
                );
            } catch (\Exception $innerException) {
                // Even this failed, give up
            }
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