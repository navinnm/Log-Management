<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Fulgid\LogManagement\Controllers\LogStreamController;
use Fulgid\LogManagement\Controllers\LogManagementController;

/*
|--------------------------------------------------------------------------
| Log Management Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the Log Management package. These routes handle
| real-time log streaming, dashboard access, and API endpoints.
|
*/

// Get route prefix from configuration
$routePrefix = config('log-management.dashboard.route_prefix', 'log-management');
$dashboardMiddleware = config('log-management.dashboard.middleware', ['web', 'log-management-auth']);
$apiMiddleware = config('log-management.api.middleware', ['api', 'log-management-auth']);

// Dashboard Routes Group
Route::group([
    'prefix' => $routePrefix,
    'middleware' => $dashboardMiddleware,
    'as' => 'log-management.',
], function () {
    
    // Main Dashboard Routes
    Route::get('/', [LogManagementController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [LogManagementController::class, 'dashboard'])->name('dashboard.index');
    
    // System Information
    Route::get('/system-info', [LogManagementController::class, 'systemInfo'])->name('system-info');
    
    // Notification Management Routes
    Route::prefix('notifications')->as('notifications.')->group(function () {
        Route::get('/settings', [LogManagementController::class, 'getNotificationSettings'])->name('settings');
        Route::post('/settings', [LogManagementController::class, 'updateNotificationSettings'])->name('update');
        Route::post('/test', [LogManagementController::class, 'testNotification'])->name('test');
    });
    
    // Log Management Routes
    Route::prefix('logs')->as('logs.')->group(function () {
        Route::delete('/clear', [LogManagementController::class, 'clearLogs'])->name('clear');
        Route::get('/export', [LogManagementController::class, 'exportLogs'])->name('export');
    });
});

// Real-time Streaming Routes Group
Route::group([
    'prefix' => $routePrefix,
    'middleware' => ['web', 'log-management-auth'],
    'as' => 'log-management.',
], function () {
    
    // Server-Sent Events stream
    Route::get('/stream', [LogStreamController::class, 'stream'])->name('stream');
    
    // Health check endpoint
    Route::get('/health', [LogStreamController::class, 'health'])->name('health');
    
    // Statistics endpoint
    Route::get('/stats', [LogStreamController::class, 'stats'])->name('stats');
    
    // SSE Test Route - Development only
    Route::get('/sse-test', function () {
        if (!app()->environment('local', 'development')) {
            abort(404);
        }
        return view('sse-test');
    })->name('sse-test');
    
    // Test Log Generation Route
    Route::post('/test-log', function (Request $request) {
        // Authentication check
        if (config('log-management.auth.enabled', false)) {
            $apiKey = $request->header('X-Log-Management-Key') ?? $request->query('key');
            $validKeys = array_filter(config('log-management.auth.api_keys', []));
            
            if (!$apiKey || !in_array($apiKey, $validKeys)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }
        
        // Validate request data
        $validated = $request->validate([
            'level' => 'required|in:emergency,alert,critical,error,warning,notice,info,debug',
            'message' => 'required|string|max:1000',
            'context' => 'array'
        ]);
        
        try {
            // Generate the test log
            Log::log(
                $validated['level'], 
                $validated['message'], 
                array_merge($validated['context'] ?? [], [
                    'test_log' => true,
                    'generated_at' => now()->toISOString(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Test log generated successfully',
                'level' => $validated['level'],
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate test log', [
                'error' => $e->getMessage(),
                'request_data' => $validated
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate log: ' . $e->getMessage()
            ], 500);
        }
    })->name('test-log');
});

// API Routes Group
Route::group([
    'prefix' => config('log-management.api.route_prefix', 'log-management/api'),
    'middleware' => $apiMiddleware,
    'as' => 'log-management.api.',
], function () {
    
    // Log Entries API Routes
    Route::prefix('logs')->as('logs.')->group(function () {
        Route::get('/', [LogStreamController::class, 'getLogs'])->name('index');
        Route::get('/search', [LogStreamController::class, 'searchLogs'])->name('search');
        Route::get('/{id}', [LogStreamController::class, 'getLog'])->name('show');
    });
    
    // Statistics API Routes
    Route::prefix('stats')->as('stats.')->group(function () {
        Route::get('/', [LogManagementController::class, 'getStats'])->name('index');
        Route::get('/summary', [LogStreamController::class, 'getStatsSummary'])->name('summary');
        Route::get('/trends', [LogStreamController::class, 'getStatsTrends'])->name('trends');
    });
    
    // System API Routes
    Route::prefix('system')->as('system.')->group(function () {
        Route::get('/health', [LogStreamController::class, 'health'])->name('health');
        Route::get('/info', [LogManagementController::class, 'systemInfo'])->name('info');
    });
    
    // Notification API Routes
    Route::prefix('notifications')->as('notifications.')->group(function () {
        Route::get('/channels', [LogManagementController::class, 'getNotificationChannels'])->name('channels');
        Route::post('/test/{channel}', [LogManagementController::class, 'testNotificationChannel'])->name('test-channel');
    });
    
    // Configuration API Routes
    Route::prefix('config')->as('config.')->group(function () {
        Route::get('/', [LogManagementController::class, 'getConfiguration'])->name('index');
        Route::post('/', [LogManagementController::class, 'updateConfiguration'])->name('update');
    });
});

// Webhook Routes Group (No authentication middleware for external services)
Route::group([
    'prefix' => $routePrefix . '/webhooks',
    'as' => 'log-management.webhooks.',
], function () {
    
    // Slack webhook endpoint for interactive components
    Route::post('/slack/actions', [LogManagementController::class, 'handleSlackAction'])
         ->name('slack.actions');
    
    // Generic webhook endpoint for external log sources
    Route::post('/ingest', [LogManagementController::class, 'ingestExternalLog'])
         ->name('ingest');
});

// Public Health Check Routes (No authentication required)
Route::prefix($routePrefix)->as('log-management.')->group(function () {
    
    // Simple ping endpoint
    Route::get('/ping', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'log-management',
            'version' => config('log-management.version', '1.0.0'),
            'environment' => app()->environment(),
        ]);
    })->name('ping');
    
    // Detailed health check
    Route::get('/health-check', function () {
        $checks = [
            'database' => true,
            'storage' => true,
            'memory' => memory_get_usage(true) < (512 * 1024 * 1024), // Less than 512MB
        ];
        
        // Check database connection
        try {
            \DB::connection()->getPdo();
        } catch (\Exception $e) {
            $checks['database'] = false;
        }
        
        // Check storage accessibility
        try {
            $testFile = storage_path('logs/test-' . time() . '.txt');
            file_put_contents($testFile, 'test');
            unlink($testFile);
        } catch (\Exception $e) {
            $checks['storage'] = false;
        }
        
        $allHealthy = array_reduce($checks, function ($carry, $check) {
            return $carry && $check;
        }, true);
        
        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
            'uptime' => app()->hasBeenBootstrapped() ? 'running' : 'starting',
        ], $allHealthy ? 200 : 503);
    })->name('health-check');
});

// Development Routes (Only available in local/development environment)
if (app()->environment('local', 'development')) {
    Route::prefix($routePrefix . '/dev')->as('log-management.dev.')->group(function () {
        
        // Generate sample logs for testing
        Route::post('/generate-sample-logs', function (Request $request) {
            $count = $request->get('count', 10);
            $levels = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];
            
            for ($i = 0; $i < $count; $i++) {
                $level = $levels[array_rand($levels)];
                $messages = [
                    'debug' => 'Debug message for testing purposes',
                    'info' => 'Information log entry generated',
                    'notice' => 'Notice: Something noteworthy happened',
                    'warning' => 'Warning: Potential issue detected',
                    'error' => 'Error: Something went wrong',
                    'critical' => 'Critical: System in critical state',
                ];
                
                Log::log($level, $messages[$level] . " #{$i}", [
                    'sample_data' => true,
                    'iteration' => $i,
                    'timestamp' => now()->toISOString(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Generated {$count} sample log entries",
                'timestamp' => now()->toISOString()
            ]);
        })->name('generate-sample-logs');
        
        // Clear all logs (development only)
        Route::delete('/clear-all-logs', function () {
            try {
                \DB::table('log_entries')->truncate();
                return response()->json([
                    'success' => true,
                    'message' => 'All logs cleared successfully',
                    'timestamp' => now()->toISOString()
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        })->name('clear-all-logs');
    });
}