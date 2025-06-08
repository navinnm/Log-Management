<?php

namespace Fulgid\LogManagement\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Models\LogEntry;
use Throwable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogManagementExceptionHandler extends ExceptionHandler
{
    protected LogNotifierService $logNotifier;

    public function __construct($container)
    {
        parent::__construct($container);
        $this->logNotifier = app(LogNotifierService::class);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // Store the exception in our log management system
        $this->logExceptionToManagement($e, $request);

        // Show our custom debug screen in debug mode
        if (config('app.debug') && config('log-management.debug.custom_screen', true)) {
            return $this->renderLogManagementDebugScreen($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Log exception to our management system.
     */
    protected function logExceptionToManagement(Throwable $e, Request $request): void
    {
        try {
            LogEntry::create([
                'level' => 'error',
                'channel' => 'exceptions',
                'message' => $e->getMessage(),
                'context' => json_encode([
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'user_id' => auth()->id(),
                ]),
                'extra' => json_encode([
                    'trace' => $e->getTraceAsString(),
                    'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
                ]),
                'created_at' => now(),
            ]);

            // Send notification if configured
            if (config('log-management.notifications.enabled')) {
                $this->logNotifier->notify(
                    "Exception: {$e->getMessage()}",
                    'error',
                    [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'url' => $request->fullUrl(),
                    ]
                );
            }
        } catch (\Exception $logException) {
            // Fallback to Laravel's default logging if our system fails
            \Log::error('Failed to log exception to Log Management: ' . $logException->getMessage());
        }
    }

    /**
     * Render our custom debug screen.
     */
    protected function renderLogManagementDebugScreen(Request $request, Throwable $e): Response
    {
        $errorId = uniqid('error_');
        
        // Get recent related logs
        $recentLogs = LogEntry::where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get similar errors
        $similarErrors = LogEntry::where('message', 'like', '%' . substr($e->getMessage(), 0, 50) . '%')
            ->where('level', 'error')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get system stats
        $stats = [
            'total_errors_today' => LogEntry::whereDate('created_at', today())
                ->whereIn('level', ['error', 'critical', 'emergency'])
                ->count(),
            'total_logs_last_hour' => LogEntry::where('created_at', '>=', now()->subHour())->count(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];

        return response()->view('log-management::debug.exception', [
            'exception' => $e,
            'request' => $request,
            'errorId' => $errorId,
            'recentLogs' => $recentLogs,
            'similarErrors' => $similarErrors,
            'stats' => $stats,
        ], 500);
    }
}