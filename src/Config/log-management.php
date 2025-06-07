<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Management Package Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Log Management
    | package. You can customize the behavior of log notifications,
    | real-time streaming, and other features here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Package Enabled
    |--------------------------------------------------------------------------
    |
    | This option determines whether the log management package is enabled.
    | When disabled, no log processing or notifications will occur.
    |
    */
    'enabled' => env('LOG_MANAGEMENT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Environment Filter
    |--------------------------------------------------------------------------
    |
    | Specify which environments should trigger log processing and notifications.
    | Leave empty to allow all environments.
    |
    */
    'environments' => [
        'production',
        'staging',
        'local', // Uncomment to enable for local development
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database logging options.
    |
    */
    'database' => [
        'enabled' => env('LOG_MANAGEMENT_DATABASE_ENABLED', true),
        'table_name' => 'log_entries',
        'auto_cleanup' => [
            'enabled' => env('LOG_MANAGEMENT_AUTO_CLEANUP_ENABLED', true),
            'retention_days' => env('LOG_MANAGEMENT_RETENTION_DAYS', 30),
            'cleanup_frequency' => 'daily', // daily, weekly, monthly
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent notification spam.
    |
    */
    'rate_limit' => [
        'enabled' => env('LOG_MANAGEMENT_RATE_LIMIT_ENABLED', true),
        'max_per_minute' => env('LOG_MANAGEMENT_RATE_LIMIT_MAX', 10),
        'cache_driver' => env('LOG_MANAGEMENT_RATE_LIMIT_CACHE', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notification settings and channels.
    |
    */
    'notifications' => [
        'enabled' => env('LOG_MANAGEMENT_NOTIFICATIONS_ENABLED', true),
        'queue' => env('LOG_MANAGEMENT_NOTIFICATIONS_QUEUE', 'default'),
        'retry_attempts' => env('LOG_MANAGEMENT_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('LOG_MANAGEMENT_RETRY_DELAY', 60), // seconds

        /*
        |--------------------------------------------------------------------------
        | Log Levels
        |--------------------------------------------------------------------------
        |
        | Specify which log levels should trigger notifications.
        | Available levels: emergency, alert, critical, error, warning, notice, info, debug
        |
        */
         'levels' => [
            'emergency',
            'alert',
            'critical',
            'error',
            // 'warning',  
            // 'notice',  
            // 'info',
            // 'warning', // Uncomment to include warnings
        ],

        /*
        |--------------------------------------------------------------------------
        | Notification Channels
        |--------------------------------------------------------------------------
        |
        | Configure individual notification channels.
        |
        */
        'channels' => [
            'email' => [
                'enabled' => env('LOG_MANAGEMENT_EMAIL_ENABLED', false),
                'to' => env('LOG_MANAGEMENT_EMAIL_TO'),
                'from' => env('LOG_MANAGEMENT_EMAIL_FROM', env('MAIL_FROM_ADDRESS')),
                'from_name' => env('LOG_MANAGEMENT_EMAIL_FROM_NAME', env('MAIL_FROM_NAME')),
                'subject_prefix' => env('LOG_MANAGEMENT_EMAIL_SUBJECT_PREFIX', '[LOG ALERT]'),
                'template' => 'log-management::emails.log-notification',
            ],

            'slack' => [
                'enabled' => env('LOG_MANAGEMENT_SLACK_ENABLED', false),
                'webhook_url' => env('LOG_MANAGEMENT_SLACK_WEBHOOK'),
                'channel' => env('LOG_MANAGEMENT_SLACK_CHANNEL', '#alerts'),
                'username' => env('LOG_MANAGEMENT_SLACK_USERNAME', 'Log Management'),
                'icon_emoji' => env('LOG_MANAGEMENT_SLACK_ICON_EMOJI', ':warning:'),
                'icon_url' => env('LOG_MANAGEMENT_SLACK_ICON_URL'),
            ],

            'webhook' => [
                'enabled' => env('LOG_MANAGEMENT_WEBHOOK_ENABLED', false),
                'url' => env('LOG_MANAGEMENT_WEBHOOK_URL'),
                'method' => env('LOG_MANAGEMENT_WEBHOOK_METHOD', 'POST'),
                'timeout' => env('LOG_MANAGEMENT_WEBHOOK_TIMEOUT', 10),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'LogManagement/1.0',
                ],
                'auth_type' => env('LOG_MANAGEMENT_WEBHOOK_AUTH_TYPE'), // none, bearer, basic, api_key
                'auth_token' => env('LOG_MANAGEMENT_WEBHOOK_AUTH_TOKEN'),
                'secret' => env('LOG_MANAGEMENT_WEBHOOK_SECRET'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Server-Sent Events (SSE) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure real-time log streaming via Server-Sent Events.
    |
    */
    'sse' => [
        'enabled' => env('LOG_MANAGEMENT_SSE_ENABLED', true),
        'endpoint' => '/log-management/stream',
        'heartbeat_interval' => 30, // seconds
        'connection_timeout' => 600, // seconds (10 minutes)
        'max_connections' => env('LOG_MANAGEMENT_SSE_MAX_CONNECTIONS', 100),

        /*
        |--------------------------------------------------------------------------
        | SSE Log Levels
        |--------------------------------------------------------------------------
        |
        | Specify which log levels should be streamed via SSE.
        |
        */
        'levels' => [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            // 'debug', // Uncomment to include debug logs
        ],

        /*
        |--------------------------------------------------------------------------
        | SSE Filters
        |--------------------------------------------------------------------------
        |
        | Additional filters for SSE streaming.
        |
        */
        'filters' => [
            'max_message_length' => 1000,
            'exclude_channels' => [
                // 'single', // Uncomment to exclude specific log channels
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configure authentication for log management endpoints.
    |
    */
    'auth' => [
        'enabled' => env('LOG_MANAGEMENT_AUTH_ENABLED', true),
        
        /*
        |--------------------------------------------------------------------------
        | API Keys
        |--------------------------------------------------------------------------
        |
        | API keys for accessing log management endpoints.
        |
        */
        'api_keys' => [
            env('LOG_MANAGEMENT_API_KEY_1'),
            env('LOG_MANAGEMENT_API_KEY_2'),
            // Add more API keys as needed
        ],

        /*
        |--------------------------------------------------------------------------
        | User Permission
        |--------------------------------------------------------------------------
        |
        | Permission required for authenticated users to access log management.
        |
        */
        'permission' => env('LOG_MANAGEMENT_PERMISSION', 'view-logs'),

        /*
        |--------------------------------------------------------------------------
        | IP Whitelist
        |--------------------------------------------------------------------------
        |
        | IP addresses that are allowed to access log management endpoints.
        | Supports CIDR notation and wildcards.
        |
        */
        'ip_whitelist' => [
            // '127.0.0.1',
            // '192.168.1.0/24',
            // '10.0.0.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the log management dashboard.
    |
    */
    'dashboard' => [
        'enabled' => env('LOG_MANAGEMENT_DASHBOARD_ENABLED', true),
        'route_prefix' => 'log-management',
        'middleware' => ['web', 'log-management-auth'],
        'logs_per_page' => 50,
        'refresh_interval' => 30, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the log management API.
    |
    */
    'api' => [
        'enabled' => env('LOG_MANAGEMENT_API_ENABLED', true),
        'route_prefix' => 'log-management/api',
        'middleware' => ['api', 'log-management-auth'],
        'rate_limit' => '60,1', // requests per minute
        'max_export_records' => 10000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Filters
    |--------------------------------------------------------------------------
    |
    | Register custom filter classes for log processing.
    |
    */
    'custom_filters' => [
        // App\LogManagement\Filters\CustomFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Notification Channels
    |--------------------------------------------------------------------------
    |
    | Register custom notification channel classes.
    |
    */
    'custom_channels' => [
        // 'discord' => App\LogManagement\Channels\DiscordChannel::class,
        // 'teams' => App\LogManagement\Channels\TeamsChannel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings.
    |
    */
    'performance' => [
        'async_processing' => env('LOG_MANAGEMENT_ASYNC_PROCESSING', true),
        'batch_size' => env('LOG_MANAGEMENT_BATCH_SIZE', 100),
        'memory_limit' => env('LOG_MANAGEMENT_MEMORY_LIMIT', '256M'),
        'execution_time_limit' => env('LOG_MANAGEMENT_TIME_LIMIT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debugging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure debugging options for development.
    |
    */
    'debug' => [
        'enabled' => env('LOG_MANAGEMENT_DEBUG', false),
        'log_channel' => env('LOG_MANAGEMENT_DEBUG_CHANNEL', 'single'),
        'verbose_errors' => env('LOG_MANAGEMENT_VERBOSE_ERRORS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Configure integrations with external services.
    |
    */
    'integrations' => [
        'sentry' => [
            'enabled' => env('LOG_MANAGEMENT_SENTRY_ENABLED', false),
            'forward_errors' => true,
        ],
        'bugsnag' => [
            'enabled' => env('LOG_MANAGEMENT_BUGSNAG_ENABLED', false),
            'forward_errors' => true,
        ],
        'new_relic' => [
            'enabled' => env('LOG_MANAGEMENT_NEW_RELIC_ENABLED', false),
            'forward_errors' => true,
        ],
    ],
];