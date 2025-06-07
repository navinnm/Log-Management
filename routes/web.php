<?php

use Illuminate\Support\Facades\Route;
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

// Dashboard Routes
Route::group([
    'prefix' => $routePrefix,
    'middleware' => $dashboardMiddleware,
    'as' => 'log-management.',
], function () {
    
    // Dashboard
    Route::get('/', [LogManagementController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard', [LogManagementController::class, 'dashboard'])->name('dashboard.index');
    
    // System Information
    Route::get('/system-info', [LogManagementController::class, 'systemInfo'])->name('system-info');
    
    // Notification Management
    Route::get('/notifications/settings', [LogManagementController::class, 'getNotificationSettings'])->name('notifications.settings');
    Route::post('/notifications/settings', [LogManagementController::class, 'updateNotificationSettings'])->name('notifications.update');
    Route::post('/notifications/test', [LogManagementController::class, 'testNotification'])->name('notifications.test');
    
    // Log Management
    Route::delete('/logs/clear', [LogManagementController::class, 'clearLogs'])->name('logs.clear');
    Route::get('/logs/export', [LogManagementController::class, 'exportLogs'])->name('logs.export');
    
});

// Real-time Streaming Routes
Route::group([
    'prefix' => $routePrefix,
    'middleware' => ['web', 'log-management-auth'],
    'as' => 'log-management.',
], function () {
    
    // Server-Sent Events stream
    Route::get('/stream', [LogStreamController::class, 'stream'])->name('stream');
    
    // Health check endpoint (public)
    Route::get('/health', [LogStreamController::class, 'health'])->name('health');
    
    // Statistics endpoint
    Route::get('/stats', [LogStreamController::class, 'stats'])->name('stats');
    Route::post('/test-log', function (Request $request) {
    // Check authentication
    if (config('log-management.auth.enabled', false)) {
        $apiKey = $request->header('X-Log-Management-Key') ?? $request->query('key');
        $validKeys = array_filter(config('log-management.auth.api_keys', []));
        
        if (!$apiKey || !in_array($apiKey, $validKeys)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
    
    // Validate request
    $validated = $request->validate([
        'level' => 'required|in:emergency,alert,critical,error,warning,notice,info,debug',
        'message' => 'required|string|max:1000',
        'context' => 'array'
    ]);
    
    try {
        // Generate the log
        Log::log(
            $validated['level'], 
            $validated['message'], 
            $validated['context'] ?? []
        );
        
        return response()->json([
            'success' => true,
            'message' => 'Test log generated successfully',
            'timestamp' => now()->toISOString()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to generate log: ' . $e->getMessage()
        ], 500);
    }
})->name('log-management.api.test-log');
    
});

// API Routes
Route::group([
    'prefix' => config('log-management.api.route_prefix', 'log-management/api'),
    'middleware' => $apiMiddleware,
    'as' => 'log-management.api.',
], function () {
    
    // Log entries API
    Route::get('/logs', [LogStreamController::class, 'getLogs'])->name('logs.index');
    Route::get('/logs/search', [LogStreamController::class, 'searchLogs'])->name('logs.search');
    Route::get('/logs/{id}', [LogStreamController::class, 'getLog'])->name('logs.show');
    
    // Statistics API
    Route::get('/stats', [LogStreamController::class, 'stats'])->name('stats');
    Route::get('/stats/summary', [LogStreamController::class, 'getStatsSummary'])->name('stats.summary');
    Route::get('/stats/trends', [LogStreamController::class, 'getStatsTrends'])->name('stats.trends');
    
    // System API
    Route::get('/health', [LogStreamController::class, 'health'])->name('health');
    Route::get('/system', [LogManagementController::class, 'systemInfo'])->name('system');
    
    // Notification API
    Route::get('/notifications/channels', [LogManagementController::class, 'getNotificationChannels'])->name('notifications.channels');
    Route::post('/notifications/test/{channel}', [LogManagementController::class, 'testNotificationChannel'])->name('notifications.test-channel');
    
    // Configuration API
    Route::get('/config', [LogManagementController::class, 'getConfiguration'])->name('config');
    Route::post('/config', [LogManagementController::class, 'updateConfiguration'])->name('config.update');
    
});

// Webhook endpoints (no auth middleware for external services)
Route::group([
    'prefix' => $routePrefix . '/webhooks',
    'as' => 'log-management.webhooks.',
], function () {
    
    // Slack webhook endpoint for interactive components
    Route::post('/slack/actions', [LogManagementController::class, 'handleSlackAction'])->name('slack.actions');
    
    // Generic webhook endpoint for external log sources
    Route::post('/ingest', [LogManagementController::class, 'ingestExternalLog'])->name('ingest');
    
});

// Public health check (no authentication required)
Route::get($routePrefix . '/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'log-management',
        'version' => '1.0.0',
    ]);
})->name('log-management.ping');