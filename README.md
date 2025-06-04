# Log Management Package for Laravel

A comprehensive Laravel package for log notification system and real-time log streaming using Server-Sent Events (SSE).

## Features

- **Real-time Log Streaming**: Stream logs in real-time using Server-Sent Events (SSE)
- **Multiple Notification Channels**: Email, Slack, and Webhook notifications
- **Configurable Log Levels**: Set which log levels trigger notifications
- **Rate Limiting**: Prevent notification spam with built-in rate limiting
- **Database Storage**: Optional database storage for log entries with auto-cleanup
- **Dashboard Interface**: Web interface for viewing and managing logs
- **REST API**: Full REST API for log management and integration
- **Authentication & Security**: API keys, IP whitelisting, and permission-based access
- **Extensible Architecture**: Easy to add custom notification channels and filters
- **Async Processing**: Optional async processing for better performance
- **Integration Support**: Built-in support for Sentry, Bugsnag, and New Relic

## Installation

### Step 1: Install the Package

```bash
composer require fulgid/log-management
```

### Step 2: Publish Configuration and Assets

```bash
# Publish the configuration file
php artisan vendor:publish --provider="Fulgid\LogManagement\LogManagementServiceProvider" --tag="log-management-config"

# Publish views (optional)
php artisan vendor:publish --provider="Fulgid\LogManagement\LogManagementServiceProvider" --tag="log-management-views"
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

### Step 4: Install Package (Optional Command)

```bash
php artisan log-management:install
```

## Configuration

The package configuration file is published to `config/log-management.php`. Here are the key configuration options:

### Basic Configuration

```php
// Enable/disable the package
'enabled' => env('LOG_MANAGEMENT_ENABLED', true),

// Environments where the package should be active
'environments' => [
    'production',
    'staging',
    // 'local', // Uncomment for local development
],
```

### Database Configuration

```php
'database' => [
    'enabled' => env('LOG_MANAGEMENT_DATABASE_ENABLED', true),
    'table_name' => 'log_entries',
    'auto_cleanup' => [
        'enabled' => env('LOG_MANAGEMENT_AUTO_CLEANUP_ENABLED', true),
        'retention_days' => env('LOG_MANAGEMENT_RETENTION_DAYS', 30),
        'cleanup_frequency' => 'daily',
    ],
],
```

### Rate Limiting

```php
'rate_limit' => [
    'enabled' => env('LOG_MANAGEMENT_RATE_LIMIT_ENABLED', true),
    'max_per_minute' => env('LOG_MANAGEMENT_RATE_LIMIT_MAX', 10),
    'cache_driver' => env('LOG_MANAGEMENT_RATE_LIMIT_CACHE', 'default'),
],
```

## Environment Variables

Add these environment variables to your `.env` file:

### Basic Settings

```env
# Package Settings
LOG_MANAGEMENT_ENABLED=true
LOG_MANAGEMENT_DATABASE_ENABLED=true
LOG_MANAGEMENT_NOTIFICATIONS_ENABLED=true

# Rate Limiting
LOG_MANAGEMENT_RATE_LIMIT_ENABLED=true
LOG_MANAGEMENT_RATE_LIMIT_MAX=10

# Auto Cleanup
LOG_MANAGEMENT_AUTO_CLEANUP_ENABLED=true
LOG_MANAGEMENT_RETENTION_DAYS=30
```

### Email Notifications

```env
LOG_MANAGEMENT_EMAIL_ENABLED=true
LOG_MANAGEMENT_EMAIL_TO=admin@yoursite.com
LOG_MANAGEMENT_EMAIL_FROM=noreply@yoursite.com
LOG_MANAGEMENT_EMAIL_FROM_NAME="Log Management"
LOG_MANAGEMENT_EMAIL_SUBJECT_PREFIX="[LOG ALERT]"
```

### Slack Notifications

```env
LOG_MANAGEMENT_SLACK_ENABLED=true
LOG_MANAGEMENT_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
LOG_MANAGEMENT_SLACK_CHANNEL=#alerts
LOG_MANAGEMENT_SLACK_USERNAME="Log Management"
LOG_MANAGEMENT_SLACK_ICON_EMOJI=:warning:
```

### Webhook Notifications

```env
LOG_MANAGEMENT_WEBHOOK_ENABLED=true
LOG_MANAGEMENT_WEBHOOK_URL=https://your-webhook-url.com/endpoint
LOG_MANAGEMENT_WEBHOOK_METHOD=POST
LOG_MANAGEMENT_WEBHOOK_TIMEOUT=10
LOG_MANAGEMENT_WEBHOOK_AUTH_TYPE=bearer
LOG_MANAGEMENT_WEBHOOK_AUTH_TOKEN=your-auth-token
```

### Server-Sent Events (SSE)

```env
LOG_MANAGEMENT_SSE_ENABLED=true
LOG_MANAGEMENT_SSE_MAX_CONNECTIONS=100
```

### Dashboard & API

```env
LOG_MANAGEMENT_DASHBOARD_ENABLED=true
LOG_MANAGEMENT_API_ENABLED=true
LOG_MANAGEMENT_AUTH_ENABLED=true
LOG_MANAGEMENT_API_KEY_1=your-api-key-1
LOG_MANAGEMENT_API_KEY_2=your-api-key-2
```

## Usage

### Basic Logging

The package automatically captures all Laravel logs. Simply use Laravel's logging as usual:

```php
use Illuminate\Support\Facades\Log;

// These will be captured and processed by the package
Log::error('Something went wrong!', ['user_id' => 123]);
Log::critical('Database connection failed');
Log::warning('API rate limit approaching');
Log::info('User logged in', ['user' => $user->email]);
```

### Dashboard Access

Access the log management dashboard at:
```
https://yoursite.com/log-management
```

### Real-time Log Streaming

Access real-time logs via Server-Sent Events:
```
https://yoursite.com/log-management/stream
```

### API Endpoints

#### Get Logs
```bash
# Get paginated logs
GET /log-management/api/logs

# Filter by level
GET /log-management/api/logs?level=error

# Filter by date range
GET /log-management/api/logs?from=2024-01-01&to=2024-01-31

# Search in messages
GET /log-management/api/logs?search=database
```

#### Get Log Statistics
```bash
GET /log-management/api/stats
```

#### Export Logs
```bash
# Export as JSON
GET /log-management/api/export?format=json

# Export as CSV
GET /log-management/api/export?format=csv
```

### Authentication

#### API Key Authentication

Include the API key in your requests:

```bash
# Header authentication
curl -H "X-API-Key: your-api-key" https://yoursite.com/log-management/api/logs

# Query parameter authentication
curl https://yoursite.com/log-management/api/logs?api_key=your-api-key
```

#### User Permission

For web dashboard access, ensure users have the required permission:

```php
// In your User model or permission system
$user->givePermissionTo('view-logs');
```

## Customization

### Custom Notification Channels

Create a custom notification channel:

```php
<?php

namespace App\LogManagement\Channels;

use Fulgid\LogManagement\Contracts\NotificationChannelInterface;

class DiscordChannel implements NotificationChannelInterface
{
    public function send(array $logData): bool
    {
        // Implement Discord webhook logic
        return true;
    }

    public function isEnabled(): bool
    {
        return config('log-management.custom_channels.discord.enabled', false);
    }
}
```

Register in `config/log-management.php`:

```php
'custom_channels' => [
    'discord' => App\LogManagement\Channels\DiscordChannel::class,
],
```

### Custom Filters

Create a custom filter:

```php
<?php

namespace App\LogManagement\Filters;

use Fulgid\LogManagement\Contracts\FilterInterface;

class CustomFilter implements FilterInterface
{
    public function filter(array $logData): bool
    {
        // Return true to process the log, false to skip
        return !str_contains($logData['message'], 'ignore this');
    }
}
```

Register in `config/log-management.php`:

```php
'custom_filters' => [
    App\LogManagement\Filters\CustomFilter::class,
],
```

### Frontend Integration

#### JavaScript SSE Client

```html
<!DOCTYPE html>
<html>
<head>
    <title>Real-time Logs</title>
</head>
<body>
    <div id="logs"></div>

    <script>
        const eventSource = new EventSource('/log-management/stream?api_key=your-api-key');
        const logsDiv = document.getElementById('logs');

        eventSource.onmessage = function(event) {
            const logData = JSON.parse(event.data);
            const logElement = document.createElement('div');
            logElement.innerHTML = `
                <strong>${logData.level}</strong> 
                [${logData.datetime}] 
                ${logData.message}
            `;
            logElement.className = `log-${logData.level}`;
            logsDiv.insertBefore(logElement, logsDiv.firstChild);
        };

        eventSource.onerror = function(event) {
            console.error('SSE connection error:', event);
        };
    </script>

    <style>
        .log-error { color: red; }
        .log-warning { color: orange; }
        .log-info { color: blue; }
        .log-debug { color: gray; }
    </style>
</body>
</html>
```

#### React Component Example

```jsx
import React, { useState, useEffect } from 'react';

const LogStream = () => {
    const [logs, setLogs] = useState([]);

    useEffect(() => {
        const eventSource = new EventSource('/log-management/stream?api_key=your-api-key');

        eventSource.onmessage = (event) => {
            const logData = JSON.parse(event.data);
            setLogs(prevLogs => [logData, ...prevLogs.slice(0, 99)]); // Keep last 100 logs
        };

        return () => {
            eventSource.close();
        };
    }, []);

    return (
        <div className="log-stream">
            <h2>Real-time Logs</h2>
            {logs.map((log, index) => (
                <div key={index} className={`log-entry log-${log.level}`}>
                    <span className="log-time">{log.datetime}</span>
                    <span className="log-level">{log.level.toUpperCase()}</span>
                    <span className="log-message">{log.message}</span>
                </div>
            ))}
        </div>
    );
};

export default LogStream;
```

## Artisan Commands

### Install Command
```bash
php artisan log-management:install [--force]
```

### Test Notifications
```bash
php artisan log-management:test [--channel=email] [--level=error]
```

### Clean Old Logs
```bash
php artisan log-management:cleanup [--days=30]
```

### Generate API Key
```bash
php artisan log-management:generate-key
```

## Performance Optimization

### Async Processing

Enable async processing for better performance:

```env
LOG_MANAGEMENT_ASYNC_PROCESSING=true
LOG_MANAGEMENT_BATCH_SIZE=100
```

### Queue Configuration

Configure queue for notifications:

```env
LOG_MANAGEMENT_NOTIFICATIONS_QUEUE=log-notifications
```

Set up a dedicated queue worker:

```bash
php artisan queue:work --queue=log-notifications
```

### Memory Management

```env
LOG_MANAGEMENT_MEMORY_LIMIT=256M
LOG_MANAGEMENT_TIME_LIMIT=60
```

## Security Considerations

### IP Whitelisting

```php
'auth' => [
    'ip_whitelist' => [
        '127.0.0.1',
        '192.168.1.0/24',
        '10.0.0.*',
    ],
],
```

### API Key Security

- Store API keys securely in environment variables
- Rotate API keys regularly
- Use different keys for different applications/environments
- Monitor API key usage

### Rate Limiting

The package includes built-in rate limiting to prevent abuse:

```env
LOG_MANAGEMENT_RATE_LIMIT_MAX=10  # Maximum notifications per minute
```

## Troubleshooting

### Common Issues

#### 1. SSE Connection Issues

```bash
# Check if SSE endpoint is accessible
curl -H "Accept: text/event-stream" https://yoursite.com/log-management/stream
```

#### 2. Notification Not Sending

```bash
# Test notifications manually
php artisan log-management:test --channel=email --level=error
```

#### 3. Database Connection Issues

```bash
# Check if migration ran successfully
php artisan migrate:status | grep log_entries
```

#### 4. Permission Denied

```bash
# Check if user has required permission
php artisan tinker
>>> auth()->user()->hasPermissionTo('view-logs')
```

### Debug Mode

Enable debug mode for detailed logging:

```env
LOG_MANAGEMENT_DEBUG=true
LOG_MANAGEMENT_VERBOSE_ERRORS=true
```

### Log Files

Check these log files for debugging:

- `storage/logs/laravel.log` - General Laravel logs
- `storage/logs/log-management.log` - Package-specific logs (if enabled)

## Integration Examples

### Slack Integration

Set up a Slack webhook:

1. Go to your Slack workspace settings
2. Create a new webhook for your channel
3. Add the webhook URL to your `.env`:

```env
LOG_MANAGEMENT_SLACK_ENABLED=true
LOG_MANAGEMENT_SLACK_WEBHOOK=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX
LOG_MANAGEMENT_SLACK_CHANNEL=#alerts
```

### Discord Integration (Custom Channel)

```php
<?php

namespace App\LogManagement\Channels;

use Fulgid\LogManagement\Contracts\NotificationChannelInterface;
use Illuminate\Support\Facades\Http;

class DiscordChannel implements NotificationChannelInterface
{
    public function send(array $logData): bool
    {
        $webhookUrl = config('log-management.custom_channels.discord.webhook_url');
        
        if (!$webhookUrl) {
            return false;
        }

        $response = Http::post($webhookUrl, [
            'content' => $this->formatMessage($logData),
            'embeds' => [
                [
                    'title' => "Log Alert: {$logData['level']}",
                    'description' => $logData['message'],
                    'color' => $this->getColorForLevel($logData['level']),
                    'timestamp' => $logData['datetime'],
                ]
            ]
        ]);

        return $response->successful();
    }

    private function formatMessage(array $logData): string
    {
        return "🚨 **{$logData['level']}** alert from " . config('app.name');
    }

    private function getColorForLevel(string $level): int
    {
        return match($level) {
            'emergency', 'alert', 'critical' => 0xFF0000, // Red
            'error' => 0xFF6600, // Orange
            'warning' => 0xFFFF00, // Yellow
            'notice', 'info' => 0x0099FF, // Blue
            default => 0x999999, // Gray
        };
    }

    public function isEnabled(): bool
    {
        return config('log-management.custom_channels.discord.enabled', false);
    }
}
```

## Testing

### Unit Tests

```bash
# Run package tests
vendor/bin/phpunit vendor/fulgid/log-management/tests

# Run specific test
vendor/bin/phpunit vendor/fulgid/log-management/tests/Unit/NotificationTest.php
```

### Feature Tests

```bash
# Test SSE streaming
vendor/bin/phpunit vendor/fulgid/log-management/tests/Feature/SseStreamingTest.php

# Test API endpoints
vendor/bin/phpunit vendor/fulgid/log-management/tests/Feature/ApiTest.php
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

## License

This package is open-source software licensed under the [MIT License](LICENSE).

## Support

- **Documentation**: [GitHub Wiki](https://github.com/navinnm/log-management/wiki)
- **Issues**: [GitHub Issues](https://github.com/navinnm/log-management/issues)
- **Discussions**: [GitHub Discussions](https://github.com/navinnm/log-management/discussions)

## Credits

- **Author**: Fulgid
- **Contributors**: [All Contributors](https://github.com/navinnm/log-management/contributors)

---

**Made with ❤️ for the Laravel community**
