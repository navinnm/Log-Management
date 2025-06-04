<?php

namespace Fulgid\LogManagement;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Fulgid\LogManagement\Commands\LogManagementInstallCommand;
use Fulgid\LogManagement\Handlers\LogHandler;
use Fulgid\LogManagement\Services\LogNotifierService;
use Fulgid\LogManagement\Services\LogStreamService;
use Fulgid\LogManagement\Services\LogFilterService;
use Fulgid\LogManagement\Notifications\Channels\EmailChannel;
use Fulgid\LogManagement\Notifications\Channels\SlackChannel;
use Fulgid\LogManagement\Notifications\Channels\WebhookChannel;

class LogManagementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/log-management.php', 'log-management');

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
            ]);
        }

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/log-management.php' => config_path('log-management.php'),
        ], 'log-management-config');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/log-management'),
        ], 'log-management-views');

        // Register log handler if enabled
        if (config('log-management.enabled', true)) {
            $this->registerLogHandler();
        }

        // Register default notification channels
        $this->registerNotificationChannels();
    }

    /**
     * Register the log handler.
     */
    protected function registerLogHandler(): void
    {
        $logHandler = new LogHandler(
            $this->app->make(LogNotifierService::class),
            $this->app->make(LogStreamService::class),
            $this->app->make(LogFilterService::class)
        );

        // Configure the log handler
        $logger = Log::getLogger();
        $logger->pushHandler($logHandler);
    }

    /**
     * Register default notification channels.
     */
    protected function registerNotificationChannels(): void
    {
        $notifierService = $this->app->make(LogNotifierService::class);

        $notifierService->addChannel('email', new EmailChannel());
        $notifierService->addChannel('slack', new SlackChannel());
        $notifierService->addChannel('webhook', new WebhookChannel());
    }
}
