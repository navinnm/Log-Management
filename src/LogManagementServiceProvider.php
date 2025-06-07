<?php

namespace Fulgid\LogManagement;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Router;
use Fulgid\LogManagement\Commands\LogManagementInstallCommand;
use Fulgid\LogManagement\Commands\LogManagementTestCommand;
use Fulgid\LogManagement\Commands\LogManagementCleanupCommand;
use Fulgid\LogManagement\Commands\LogManagementTestNotificationCommand;
use Fulgid\LogManagement\Commands\LogManagementDebugCommand;
use Fulgid\LogManagement\Commands\LogManagementShowcaseCommand;
use Fulgid\LogManagement\Handlers\LogHandler;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Services\LogFilterService;
use Fulgid\LogManagement\Notifications\Channels\EmailChannel;
use Fulgid\LogManagement\Notifications\Channels\SlackChannel;
use Fulgid\LogManagement\Notifications\Channels\WebhookChannel;
use Fulgid\LogManagement\Middleware\LogManagementAuth;

class LogManagementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(__DIR__ . '/Config/log-management.php', 'log-management');

        // Register services
        $this->app->singleton(LogNotifierService::class, function ($app) {
            return new LogNotifierService();
        });

        $this->app->singleton(LogStreamService::class, function ($app) {
            return new LogStreamService();
        });

        $this->app->singleton(LogFilterService::class, function ($app) {
            return new LogFilterService();
        });

        // Register facade
        $this->app->bind('log-management', function ($app) {
            return $app->make(LogNotifierService::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register middleware
        $this->registerMiddleware();

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'log-management');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                LogManagementInstallCommand::class,
                LogManagementTestCommand::class,
                LogManagementCleanupCommand::class,
                LogManagementTestNotificationCommand::class,
                LogManagementDebugCommand::class,
                LogManagementShowcaseCommand::class,
            ]);
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/Config/log-management.php' => config_path('log-management.php'),
        ], 'log-management-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/log-management'),
        ], 'log-management-views');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'log-management-migrations');

        // Register log handler if enabled
        if (config('log-management.enabled', true)) {
            $this->registerLogHandler();
        }

        // Register default notification channels
        $this->registerNotificationChannels();
    }

    /**
     * Register middleware.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        
        // Register the middleware
        $router->aliasMiddleware('log-management-auth', LogManagementAuth::class);
        
        // Alternative method for older Laravel versions
        if (method_exists($router, 'middleware')) {
            $router->middleware('log-management-auth', LogManagementAuth::class);
        }
    }

    /**
     * Register the log handler.
     */
    protected function registerLogHandler(): void
    {
        try {
            $logHandler = new LogHandler(
                $this->app->make(LogNotifierService::class),
                $this->app->make(LogStreamService::class),
                $this->app->make(LogFilterService::class)
            );

            // Configure the log handler
            $logger = Log::getLogger();
            $logger->pushHandler($logHandler);
            
        } catch (\Exception $e) {
            // Silently fail to prevent application from breaking
            // Log to a separate error file
            $this->logError('Failed to register log handler: ' . $e->getMessage());
        }
    }

    /**
     * Register default notification channels.
     */
    protected function registerNotificationChannels(): void
    {
        try {
            $notifierService = $this->app->make(LogNotifierService::class);

            $notifierService->addChannel('email', new EmailChannel());
            $notifierService->addChannel('slack', new SlackChannel());
            $notifierService->addChannel('webhook', new WebhookChannel());
            
        } catch (\Exception $e) {
            // Silently fail to prevent application from breaking
            $this->logError('Failed to register notification channels: ' . $e->getMessage());
        }
    }

    /**
     * Log errors to a separate file to avoid infinite loops.
     */
    protected function logError(string $message): void
    {
        try {
            $errorFile = storage_path('logs/log-management-provider.log');
            $timestamp = date('Y-m-d H:i:s');
            $errorMessage = "[$timestamp] LOG_MANAGEMENT_PROVIDER_ERROR: $message\n";
            
            file_put_contents($errorFile, $errorMessage, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Ultimate fallback - do nothing to prevent any loops
        }
    }
}