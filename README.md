# 📊 Log Management Package for Laravel

<div align="center">

[![Latest Version](https://img.shields.io/packagist/v/fulgid/log-management.svg?style=for-the-badge&logo=packagist)](https://packagist.org/packages/fulgid/log-management)
[![Total Downloads](https://img.shields.io/packagist/dt/fulgid/log-management.svg?style=for-the-badge&logo=packagist)](https://packagist.org/packages/fulgid/log-management)
[![License](https://img.shields.io/packagist/l/fulgid/log-management.svg?style=for-the-badge)](LICENSE)
[![GitHub Tests](https://img.shields.io/github/actions/workflow/status/fulgid/log-management/tests.yml?style=for-the-badge&logo=github&label=Tests)](https://github.com/fulgid/log-management/actions)
[![PHP Version](https://img.shields.io/packagist/php-v/fulgid/log-management.svg?style=for-the-badge&logo=php)](https://packagist.org/packages/fulgid/log-management)

**🚀 The most comprehensive Laravel log monitoring solution with real-time streaming, intelligent alerts, and multi-channel notifications**

<!-- [📖 Documentation](https://docs.fulgid.com/log-management) • [🎬 Video Tour](https://demo.fulgid.com) • [💬 Community](https://github.com/fulgid/log-management/discussions) • [🐛 Report Bug](https://github.com/fulgid/log-management/issues) -->

</div>

---

## ✨ Key Features

<table>
<tr>
<td width="50%">

### 🔔 **Smart Notifications**
- Multi-channel alerts (Email, Slack, Discord, Webhooks)
- Intelligent rate limiting & de-duplication
- Escalation rules & auto-resolution
- Custom notification templates

### 📡 **Real-time Monitoring**
- Server-Sent Events (SSE) streaming
- Live dashboard with beautiful charts
- WebSocket support for instant updates
- Mobile-responsive interface

</td>
<td width="50%">

### 🧠 **Advanced Analytics**
- Log aggregation & pattern detection
- Anomaly detection with ML algorithms
- Trend analysis & forecasting
- Performance metrics & insights

### 🔧 **Developer Experience**
- Zero-config setup with sensible defaults
- Comprehensive API with OpenAPI docs
- Laravel Telescope integration
- Extensive customization options

</td>
</tr>
</table>

---

## 🚀 Quick Start

### ⚡ Installation (30 seconds)

```bash
# 1. Install package
composer require fulgid/log-management

# 2. One-command setup
php artisan log-management:install

# 3. Start monitoring! 🎉
```

That's it! Your logs are now being monitored. Visit `/log-management` to see your dashboard.

### 🎯 Test Drive

```php
// Trigger a test alert
Log::error('Houston, we have a problem!', ['user_id' => 123]);

// Or use the facade for custom alerts
LogManagement::alert('Payment gateway is down!', [
    'severity' => 'critical',
    'service' => 'payments'
]);
```

---

## 📋 Table of Contents

<details>
<summary>📖 Click to expand navigation</summary>

- [🔧 Installation & Setup](#-installation--setup)
- [⚙️ Configuration](#️-configuration)
- [🎛️ Dashboard & Monitoring](#️-dashboard--monitoring)
- [🔌 API Reference](#-api-reference)
- [🔔 Notification Channels](#-notification-channels)
- [📡 Real-time Streaming](#-real-time-streaming)
- [🎨 Frontend Integration](#-frontend-integration)
- [🛠️ Commands & Tools](#️-commands--tools)
- [🚀 Deployment](#-deployment)
- [🔍 Troubleshooting](#-troubleshooting)
- [🤝 Contributing](#-contributing)

</details>

---

## 📋 Requirements

<table>
<tr>
<td>

**🐘 PHP Requirements**
- PHP 8.1+ (8.2+ recommended)
- Extensions: `json`, `mbstring`, `curl`

</td>
<td>

**🎸 Laravel Support**
- Laravel 10.x or 11.x
- Queue driver (Redis recommended)

</td>
<td>

**🗄️ Database Support**
- MySQL 8.0+
- PostgreSQL 13+
- SQLite 3.35+

</td>
</tr>
</table>

---

## 🔧 Installation & Setup

### 📦 Step 1: Install Package

```bash
composer require fulgid/log-management
```

### ⚙️ Step 2: Run Installation Wizard

```bash
php artisan log-management:install
```

<details>
<summary>🔍 What the installer does</summary>

✅ Publishes configuration files  
✅ Runs database migrations  
✅ Generates secure API keys  
✅ Creates storage directories  
✅ Tests your notification channels  
✅ Shows you next steps

</details>

### 🎛️ Step 3: Configure Environment

Add to your `.env` file:

```env
# 🔧 Core Configuration
LOG_MANAGEMENT_ENABLED=true
LOG_MANAGEMENT_ENVIRONMENTS=production,staging

# 🔔 Notifications
LOG_MANAGEMENT_EMAIL_TO=admin@yourapp.com
LOG_MANAGEMENT_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK

# 🔐 Security
LOG_MANAGEMENT_API_KEY_1=lm_your_secure_api_key_here

# 📊 Performance
LOG_MANAGEMENT_RETENTION_DAYS=30
LOG_MANAGEMENT_RATE_LIMIT_MAX=10
```

### ✅ Step 4: Verify Installation

```bash
# Test your setup
php artisan log-management:test --all

# Check system health
php artisan log-management:health
```

---

## ⚙️ Configuration

### 🔧 Core Settings

<details>
<summary>📝 Basic Configuration</summary>

```env
# Package Control
LOG_MANAGEMENT_ENABLED=true
LOG_MANAGEMENT_DEBUG=false
LOG_MANAGEMENT_ENVIRONMENTS=production,staging

# Database Settings
LOG_MANAGEMENT_DATABASE_ENABLED=true
LOG_MANAGEMENT_AUTO_CLEANUP_ENABLED=true
LOG_MANAGEMENT_RETENTION_DAYS=30

# Performance Tuning
LOG_MANAGEMENT_BATCH_SIZE=100
LOG_MANAGEMENT_MEMORY_LIMIT=256M
LOG_MANAGEMENT_ASYNC_PROCESSING=true
```

</details>

### 🔔 Notification Channels

<details>
<summary>📧 Email Configuration</summary>

```env
LOG_MANAGEMENT_EMAIL_ENABLED=true
LOG_MANAGEMENT_EMAIL_TO=admin@yourapp.com,devteam@yourapp.com
LOG_MANAGEMENT_EMAIL_FROM=alerts@yourapp.com
LOG_MANAGEMENT_EMAIL_FROM_NAME="🚨 Log Management System"
LOG_MANAGEMENT_EMAIL_SUBJECT_PREFIX="[ALERT]"
LOG_MANAGEMENT_EMAIL_TEMPLATE=custom-alert-template
```

</details>

<details>
<summary>💬 Slack Configuration</summary>

```env
LOG_MANAGEMENT_SLACK_ENABLED=true
LOG_MANAGEMENT_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK
LOG_MANAGEMENT_SLACK_CHANNEL=#alerts
LOG_MANAGEMENT_SLACK_USERNAME="🤖 Log Bot"
LOG_MANAGEMENT_SLACK_ICON_EMOJI=:warning:
LOG_MANAGEMENT_SLACK_MENTION_USERS=@channel,@devteam
```

</details>

<details>
<summary>🔗 Webhook Configuration</summary>

```env
LOG_MANAGEMENT_WEBHOOK_ENABLED=true
LOG_MANAGEMENT_WEBHOOK_URL=https://your-monitoring-service.com/webhooks/logs
LOG_MANAGEMENT_WEBHOOK_METHOD=POST
LOG_MANAGEMENT_WEBHOOK_TIMEOUT=10
LOG_MANAGEMENT_WEBHOOK_AUTH_TYPE=bearer
LOG_MANAGEMENT_WEBHOOK_AUTH_TOKEN=your-secret-token
LOG_MANAGEMENT_WEBHOOK_RETRY_ATTEMPTS=3
```

</details>

### 🔐 Security & Authentication

<details>
<summary>🛡️ Security Settings</summary>

```env
# Authentication
LOG_MANAGEMENT_AUTH_ENABLED=true
LOG_MANAGEMENT_PERMISSION=view-logs

# API Keys (auto-generated during install)
LOG_MANAGEMENT_API_KEY_1=lm_your_generated_api_key_1
LOG_MANAGEMENT_API_KEY_2=lm_your_generated_api_key_2

# Rate Limiting
LOG_MANAGEMENT_RATE_LIMIT_ENABLED=true
LOG_MANAGEMENT_RATE_LIMIT_MAX=60
LOG_MANAGEMENT_RATE_LIMIT_WINDOW=60

# IP Restrictions (optional)
LOG_MANAGEMENT_IP_WHITELIST=192.168.1.0/24,10.0.0.0/8
```

</details>

### 📡 Real-time Streaming

<details>
<summary>🌊 SSE Configuration</summary>

```env
# Server-Sent Events
LOG_MANAGEMENT_SSE_ENABLED=true
LOG_MANAGEMENT_SSE_MAX_CONNECTIONS=100
LOG_MANAGEMENT_SSE_HEARTBEAT_INTERVAL=30
LOG_MANAGEMENT_SSE_BUFFER_SIZE=1024

# WebSocket Support (optional)
LOG_MANAGEMENT_WEBSOCKET_ENABLED=false
LOG_MANAGEMENT_WEBSOCKET_PORT=6001
```

</details>

---

## 🎛️ Dashboard & Monitoring

### 📊 Web Dashboard

Access your beautiful dashboard at: **`https://yourapp.com/log-management`**

<table>
<tr>
<td width="50%">

**📈 Analytics View**
- Real-time log volume charts
- Error rate trends
- Response time metrics
- Custom time ranges

</td>
<td width="50%">

**🔍 Search & Filter**
- Full-text log search
- Advanced filtering by level, date, user
- Saved search queries
- Export capabilities

</td>
</tr>
</table>

### 🎯 Key Metrics

| Metric | Description | Alert Threshold |
|--------|-------------|-----------------|
| **Error Rate** | Errors per minute | > 10/min |
| **Response Time** | Avg. response time | > 2000ms |
| **Memory Usage** | Peak memory usage | > 80% |
| **Queue Depth** | Pending jobs | > 1000 |

### 📱 Mobile Dashboard

The dashboard is fully responsive and works perfectly on:
- 📱 Mobile phones
- 📱 Tablets  
- 💻 Desktop computers
- 🖥️ Large monitors

---

## 🔌 API Reference

### 🔑 Authentication

Include your API key in requests using any method:

```bash
# Method 1: Header (Recommended)
curl -H "X-Log-Management-Key: lm_your_api_key" \
     https://yourapp.com/log-management/api/logs

# Method 2: Bearer Token
curl -H "Authorization: Bearer lm_your_api_key" \
     https://yourapp.com/log-management/api/logs

# Method 3: Query Parameter
curl "https://yourapp.com/log-management/api/logs?key=lm_your_api_key"
```

### 📋 Core Endpoints

<details>
<summary>📊 Log Management</summary>

```bash
# Get paginated logs
GET /log-management/api/logs
  ?level[]=error&level[]=critical     # Filter by levels
  &search=database                    # Search in messages
  &from=2024-01-01&to=2024-01-31     # Date range
  &user_id=123                        # Filter by user
  &per_page=50&page=2                # Pagination

# Get specific log entry
GET /log-management/api/logs/{id}

# Get log statistics
GET /log-management/api/stats
  ?period=24h                         # 1h, 24h, 7d, 30d
  &group_by=level                     # level, channel, hour

# Export logs
GET /log-management/api/logs/export
  ?format=csv                         # csv, json, excel
  &filters[level]=error
```

</details>

<details>
<summary>🔔 Notification Management</summary>

```bash
# Test notifications
POST /log-management/api/notifications/test
{
  "channel": "slack",                 # email, slack, webhook, all
  "message": "Test notification",
  "level": "error"
}

# Get notification settings
GET /log-management/api/notifications/settings

# Update notification settings
PUT /log-management/api/notifications/settings
{
  "email": {
    "enabled": true,
    "recipients": ["admin@app.com"]
  }
}

# Get notification history
GET /log-management/api/notifications/history
```

</details>

<details>
<summary>⚙️ System Management</summary>

```bash
# System health check
GET /log-management/api/health

# System information
GET /log-management/api/system

# Clear old logs
DELETE /log-management/api/logs/cleanup
  ?days=7                             # Older than X days
  &level=debug                        # Specific level only

# Get configuration
GET /log-management/api/config
```

</details>

### 📊 Response Examples

<details>
<summary>📋 Log List Response</summary>

```json
{
  "data": [
    {
      "id": 1,
      "level": "error",
      "message": "Database connection failed",
      "channel": "database",
      "context": {
        "user_id": 123,
        "connection": "mysql",
        "query_time": 5.2
      },
      "extra": {
        "file": "/app/Database/Connection.php",
        "line": 157
      },
      "formatted": "[2024-01-15 10:30:15] production.ERROR: Database connection failed",
      "timestamp": "2024-01-15T10:30:15.000000Z",
      "created_at": "2024-01-15T10:30:15.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 20,
    "total": 195,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/logs?page=1",
    "last": "/api/logs?page=10",
    "prev": null,
    "next": "/api/logs?page=2"
  }
}
```

</details>

<details>
<summary>📈 Statistics Response</summary>

```json
{
  "summary": {
    "total_logs": 12450,
    "total_today": 234,
    "total_errors": 45,
    "error_rate": 3.6,
    "avg_response_time": 245.7
  },
  "level_breakdown": {
    "emergency": 0,
    "alert": 2,
    "critical": 5,
    "error": 38,
    "warning": 156,
    "notice": 234,
    "info": 1890,
    "debug": 10125
  },
  "hourly_stats": [
    {
      "hour": "2024-01-15T10:00:00Z",
      "count": 45,
      "errors": 3,
      "avg_response_time": 234.5
    }
  ],
  "top_errors": [
    {
      "message": "Database connection timeout",
      "count": 23,
      "last_seen": "2024-01-15T10:25:00Z"
    }
  ]
}
```

</details>

---

## 🔔 Notification Channels

### 📧 Email Notifications

Rich HTML emails with detailed log information:

```php
// Custom email template
LogManagement::emailTemplate('critical-alert', function ($log) {
    return [
        'subject' => "🚨 Critical Error: {$log['message']}",
        'template' => 'emails.critical-alert',
        'data' => [
            'log' => $log,
            'dashboard_url' => route('log-management.dashboard'),
            'action_required' => true
        ]
    ];
});
```

### 💬 Slack Integration

Beautiful Slack messages with contextual information:

```json
{
  "attachments": [
    {
      "color": "danger",
      "title": "🚨 Critical Error Detected",
      "text": "Database connection failed",
      "fields": [
        {"title": "Level", "value": "error", "short": true},
        {"title": "Environment", "value": "production", "short": true},
        {"title": "User ID", "value": "123", "short": true},
        {"title": "Time", "value": "2024-01-15 10:30:15", "short": true}
      ],
      "actions": [
        {
          "type": "button",
          "text": "View Dashboard",
          "url": "https://yourapp.com/log-management"
        }
      ]
    }
  ]
}
```

### 🔗 Webhook Notifications

Flexible webhook payloads for external integrations:

```json
{
  "event": "log.error",
  "timestamp": "2024-01-15T10:30:15Z",
  "environment": "production",
  "log": {
    "id": 1,
    "level": "error",
    "message": "Database connection failed",
    "context": {"user_id": 123},
    "metadata": {
      "file": "/app/Database/Connection.php",
      "line": 157
    }
  },
  "application": {
    "name": "Your App",
    "url": "https://yourapp.com",
    "version": "1.2.3"
  },
  "signature": "sha256=calculated_signature"
}
```

### 🎨 Custom Channels

Create custom notification channels:

```php
use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;

class DiscordChannel implements NotificationChannelInterface
{
    public function send(array $logData): bool
    {
        $webhook = config('services.discord.webhook_url');
        
        $payload = [
            'content' => "🚨 **{$logData['level']}**: {$logData['message']}",
            'embeds' => [
                [
                    'color' => $this->getColorForLevel($logData['level']),
                    'timestamp' => $logData['timestamp'],
                    'fields' => $this->formatFields($logData)
                ]
            ]
        ];
        
        return Http::post($webhook, $payload)->successful();
    }
    
    private function getColorForLevel($level): int
    {
        return match($level) {
            'emergency', 'alert', 'critical' => 0xFF0000, // Red
            'error' => 0xFF4500,                          // Orange Red
            'warning' => 0xFFA500,                        // Orange
            'notice' => 0x0000FF,                         // Blue
            'info' => 0x00FF00,                           // Green
            'debug' => 0x808080,                          // Gray
            default => 0x000000                           // Black
        };
    }
    
    private function formatFields(array $logData): array
    {
        $fields = [
            ['name' => 'Level', 'value' => ucfirst($logData['level']), 'inline' => true],
            ['name' => 'Environment', 'value' => $logData['environment'] ?? 'Unknown', 'inline' => true]
        ];
        
        if (!empty($logData['context']['user_id'])) {
            $fields[] = ['name' => 'User ID', 'value' => $logData['context']['user_id'], 'inline' => true];
        }
        
        return $fields;
    }
}

// Register in AppServiceProvider
LogManagement::addChannel('discord', new DiscordChannel());
```

---

## 📡 Real-time Streaming

### 🌊 Server-Sent Events (SSE)

Connect to real-time log streams:

```javascript
const eventSource = new EventSource('/log-management/stream?key=your-api-key');

eventSource.onmessage = function(event) {
    const logData = JSON.parse(event.data);
    
    // Handle different event types
    switch(logData.type) {
        case 'log':
            displayLog(logData);
            break;
        case 'stats':
            updateMetrics(logData);
            break;
        case 'heartbeat':
            updateConnectionStatus('connected');
            break;
    }
};

eventSource.onerror = function(event) {
    updateConnectionStatus('disconnected');
    console.error('SSE connection error:', event);
};
```

### 🎛️ Stream Filtering

Filter streams by various criteria:

```javascript
// Filter by log levels
const errorStream = new EventSource(
    '/log-management/stream?level[]=error&level[]=critical&key=your-api-key'
);

// Filter by user or context
const userStream = new EventSource(
    '/log-management/stream?user_id=123&key=your-api-key'
);

// Include recent history
const streamWithHistory = new EventSource(
    '/log-management/stream?include_recent=50&key=your-api-key'
);

// Multiple filters
const filteredStream = new EventSource(
    '/log-management/stream?level[]=error&channel=database&since=1h&key=your-api-key'
);
```

---

## 🎨 Frontend Integration

### 🖥️ Complete Dashboard Example

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚨 Log Management Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .log-entry { 
            @apply mb-2 p-3 rounded-lg border-l-4 transition-all duration-200 hover:shadow-md; 
        }
        .log-emergency { @apply border-red-600 bg-red-50 text-red-900; }
        .log-alert { @apply border-red-500 bg-red-50 text-red-800; }
        .log-critical { @apply border-red-400 bg-red-50 text-red-700; }
        .log-error { @apply border-red-300 bg-red-50 text-red-600; }
        .log-warning { @apply border-yellow-400 bg-yellow-50 text-yellow-800; }
        .log-notice { @apply border-blue-400 bg-blue-50 text-blue-800; }
        .log-info { @apply border-blue-300 bg-blue-50 text-blue-700; }
        .log-debug { @apply border-gray-300 bg-gray-50 text-gray-700; }
        
        .status-indicator {
            @apply inline-block w-3 h-3 rounded-full mr-2;
        }
        .status-connected { @apply bg-green-500 animate-pulse; }
        .status-disconnected { @apply bg-red-500; }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-out {
            animation: slideOut 0.3s ease-out forwards;
        }
        
        @keyframes slideOut {
            to { transform: translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                🚨 Log Management Dashboard
            </h1>
            <div class="flex items-center">
                <span class="status-indicator status-connected" id="connection-status"></span>
                <span class="text-sm text-gray-600" id="connection-text">Connected</span>
                <span class="ml-4 text-sm text-gray-500" id="last-update">Last update: Never</span>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="text-3xl">📊</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Logs</p>
                        <p class="text-2xl font-bold text-gray-900" id="total-logs">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="text-3xl">🚨</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Errors Today</p>
                        <p class="text-2xl font-bold text-red-600" id="errors-today">0</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="text-3xl">⚡</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Error Rate</p>
                        <p class="text-2xl font-bold text-orange-600" id="error-rate">0%</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="text-3xl">🔗</div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Connections</p>
                        <p class="text-2xl font-bold text-green-600" id="connections">1</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">📈 Log Volume (Last 24h)</h3>
                <canvas id="volume-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-4">🍩 Log Levels</h3>
                <canvas id="levels-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Controls -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter Level:</label>
                    <select id="level-filter" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All Levels</option>
                        <option value="emergency">Emergency</option>
                        <option value="alert">Alert</option>
                        <option value="critical">Critical</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="notice">Notice</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search:</label>
                    <input type="text" id="search-input" placeholder="Search logs..." 
                           class="border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <div class="flex items-end gap-2">
                    <button onclick="clearLogs()" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">
                        🗑️ Clear
                    </button>
                    <button onclick="exportLogs()" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        📥 Export
                    </button>
                    <button onclick="toggleStream()" id="stream-toggle" 
                            class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                        ⏸️ Pause
                    </button>
                </div>
            </div>
        </div>

        <!-- Live Logs -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold">📋 Live Logs</h3>
                <p class="text-sm text-gray-600">Real-time log entries as they happen</p>
            </div>
            <div id="logs-container" class="p-6 max-h-96 overflow-y-auto">
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-2">👀</div>
                    <p>Waiting for logs...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const API_KEY = 'your-api-key-here';
        const BASE_URL = '/log-management';
        
        // State
        let eventSource = null;
        let isStreamActive = true;
        let logs = [];
        let filteredLogs = [];
        let metrics = {
            totalLogs: 0,
            errorsToday: 0,
            errorRate: 0,
            connections: 1
        };
        
        // DOM Elements
        const logsContainer = document.getElementById('logs-container');
        const connectionStatus = document.getElementById('connection-status');
        const connectionText = document.getElementById('connection-text');
        const lastUpdate = document.getElementById('last-update');
        const levelFilter = document.getElementById('level-filter');
        const searchInput = document.getElementById('search-input');
        const streamToggle = document.getElementById('stream-toggle');
        
        // Metrics Elements
        const totalLogsEl = document.getElementById('total-logs');
        const errorsTodayEl = document.getElementById('errors-today');
        const errorRateEl = document.getElementById('error-rate');
        const connectionsEl = document.getElementById('connections');
        
        // Charts
        let volumeChart, levelsChart;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            connectToStream();
            loadInitialData();
            setupEventListeners();
        });
        
        function initializeCharts() {
            // Volume Chart
            const volumeCtx = document.getElementById('volume-chart').getContext('2d');
            volumeChart = new Chart(volumeCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Log Volume',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Levels Chart
            const levelsCtx = document.getElementById('levels-chart').getContext('2d');
            levelsChart = new Chart(levelsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Emergency', 'Alert', 'Critical', 'Error', 'Warning', 'Notice', 'Info', 'Debug'],
                    datasets: [{
                        data: [0, 0, 0, 0, 0, 0, 0, 0],
                        backgroundColor: [
                            '#DC2626', // Emergency - Red
                            '#EF4444', // Alert - Light Red
                            '#F87171', // Critical - Lighter Red
                            '#FCA5A5', // Error - Pink
                            '#FBBF24', // Warning - Yellow
                            '#60A5FA', // Notice - Blue
                            '#34D399', // Info - Green
                            '#9CA3AF'  // Debug - Gray
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function connectToStream() {
            if (eventSource) {
                eventSource.close();
            }
            
            const url = `${BASE_URL}/stream?key=${API_KEY}`;
            eventSource = new EventSource(url);
            
            eventSource.onopen = function() {
                updateConnectionStatus('connected');
                console.log('✅ Connected to log stream');
            };
            
            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleStreamMessage(data);
                } catch (error) {
                    console.error('Error parsing stream data:', error);
                }
            };
            
            eventSource.onerror = function(event) {
                updateConnectionStatus('disconnected');
                console.error('❌ Stream connection error:', event);
                
                // Attempt to reconnect after 5 seconds
                setTimeout(() => {
                    if (isStreamActive) {
                        connectToStream();
                    }
                }, 5000);
            };
        }
        
        function handleStreamMessage(data) {
            updateLastUpdate();
            
            switch(data.type) {
                case 'log':
                    addLogEntry(data);
                    break;
                case 'stats':
                    updateMetrics(data);
                    break;
                case 'heartbeat':
                    // Keep connection alive
                    break;
                default:
                    console.log('Unknown message type:', data.type);
            }
        }
        
        function addLogEntry(logData) {
            logs.unshift(logData);
            
            // Keep only last 100 logs in memory
            if (logs.length > 100) {
                logs = logs.slice(0, 100);
            }
            
            applyFilters();
            renderLogs();
            
            // Update volume chart
            updateVolumeChart();
            
            // Update levels chart
            updateLevelsChart();
        }
        
        function updateMetrics(data) {
            metrics = { ...metrics, ...data };
            
            totalLogsEl.textContent = formatNumber(metrics.totalLogs);
            errorsTodayEl.textContent = formatNumber(metrics.errorsToday);
            errorRateEl.textContent = `${metrics.errorRate.toFixed(1)}%`;
            connectionsEl.textContent = formatNumber(metrics.connections);
        }
        
        function updateVolumeChart() {
            const now = new Date();
            const timeLabel = now.toLocaleTimeString();
            
            // Add new data point
            volumeChart.data.labels.push(timeLabel);
            volumeChart.data.datasets[0].data.push(logs.length);
            
            // Keep only last 20 data points
            if (volumeChart.data.labels.length > 20) {
                volumeChart.data.labels.shift();
                volumeChart.data.datasets[0].data.shift();
            }
            
            volumeChart.update('none');
        }
        
        function updateLevelsChart() {
            const levelCounts = {
                emergency: 0, alert: 0, critical: 0, error: 0,
                warning: 0, notice: 0, info: 0, debug: 0
            };
            
            logs.forEach(log => {
                if (levelCounts.hasOwnProperty(log.level)) {
                    levelCounts[log.level]++;
                }
            });
            
            levelsChart.data.datasets[0].data = Object.values(levelCounts);
            levelsChart.update('none');
        }
        
        function renderLogs() {
            if (filteredLogs.length === 0) {
                logsContainer.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <div class="text-4xl mb-2">🔍</div>
                        <p>No logs match your filters</p>
                    </div>
                `;
                return;
            }
            
            const logsHtml = filteredLogs.slice(0, 50).map(log => createLogEntryHtml(log)).join('');
            logsContainer.innerHTML = logsHtml;
        }
        
        function createLogEntryHtml(log) {
            const timestamp = new Date(log.timestamp).toLocaleString();
            const levelIcon = getLevelIcon(log.level);
            const contextJson = log.context ? JSON.stringify(log.context, null, 2) : '{}';
            
            return `
                <div class="log-entry log-${log.level} fade-in" data-log-id="${log.id}">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <span class="text-lg mr-2">${levelIcon}</span>
                                <span class="font-semibold text-sm uppercase tracking-wide">${log.level}</span>
                                <span class="ml-2 text-xs text-gray-500">${timestamp}</span>
                                ${log.channel ? `<span class="ml-2 text-xs bg-gray-200 px-2 py-1 rounded">${log.channel}</span>` : ''}
                            </div>
                            <div class="mb-2">
                                <p class="font-medium">${escapeHtml(log.message)}</p>
                            </div>
                            ${Object.keys(log.context || {}).length > 0 ? `
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs text-gray-600 hover:text-gray-800">
                                        View Context
                                    </summary>
                                    <pre class="mt-2 text-xs bg-gray-100 p-2 rounded overflow-x-auto"><code>${escapeHtml(contextJson)}</code></pre>
                                </details>
                            ` : ''}
                        </div>
                        <button onclick="removeLogEntry('${log.id}')" class="ml-4 text-gray-400 hover:text-red-500 transition-colors">
                            ✕
                        </button>
                    </div>
                </div>
            `;
        }
        
        function getLevelIcon(level) {
            const icons = {
                emergency: '🆘',
                alert: '🚨',
                critical: '🔥',
                error: '❌',
                warning: '⚠️',
                notice: 'ℹ️',
                info: '💡',
                debug: '🐛'
            };
            return icons[level] || '📝';
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function applyFilters() {
            filteredLogs = logs.filter(log => {
                // Level filter
                const selectedLevel = levelFilter.value;
                if (selectedLevel && log.level !== selectedLevel) {
                    return false;
                }
                
                // Search filter
                const searchTerm = searchInput.value.toLowerCase();
                if (searchTerm && !log.message.toLowerCase().includes(searchTerm)) {
                    return false;
                }
                
                return true;
            });
        }
        
        function setupEventListeners() {
            levelFilter.addEventListener('change', () => {
                applyFilters();
                renderLogs();
            });
            
            searchInput.addEventListener('input', debounce(() => {
                applyFilters();
                renderLogs();
            }, 300));
            
            // Keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case 'k':
                            e.preventDefault();
                            searchInput.focus();
                            break;
                        case 'r':
                            e.preventDefault();
                            location.reload();
                            break;
                    }
                }
            });
        }
        
        function updateConnectionStatus(status) {
            connectionStatus.className = `status-indicator status-${status}`;
            connectionText.textContent = status === 'connected' ? 'Connected' : 'Disconnected';
        }
        
        function updateLastUpdate() {
            lastUpdate.textContent = `Last update: ${new Date().toLocaleTimeString()}`;
        }
        
        function formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }
        
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        function loadInitialData() {
            fetch(`${BASE_URL}/api/logs?key=${API_KEY}&per_page=20`)
                .then(response => response.json())
                .then(data => {
                    logs = data.data || [];
                    applyFilters();
                    renderLogs();
                })
                .catch(error => console.error('Error loading initial data:', error));
                
            fetch(`${BASE_URL}/api/stats?key=${API_KEY}`)
                .then(response => response.json())
                .then(data => {
                    updateMetrics(data.summary || {});
                })
                .catch(error => console.error('Error loading stats:', error));
        }
        
        // Control Functions
        function clearLogs() {
            if (confirm('Are you sure you want to clear all displayed logs?')) {
                logs = [];
                filteredLogs = [];
                renderLogs();
            }
        }
        
        function exportLogs() {
            const data = filteredLogs.map(log => ({
                timestamp: log.timestamp,
                level: log.level,
                message: log.message,
                channel: log.channel,
                context: log.context
            }));
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `logs-${new Date().toISOString().slice(0, 10)}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
        
        function toggleStream() {
            isStreamActive = !isStreamActive;
            
            if (isStreamActive) {
                connectToStream();
                streamToggle.innerHTML = '⏸️ Pause';
                streamToggle.className = 'bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600';
            } else {
                if (eventSource) {
                    eventSource.close();
                }
                updateConnectionStatus('disconnected');
                streamToggle.innerHTML = '▶️ Resume';
                streamToggle.className = 'bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600';
            }
        }
        
        function removeLogEntry(logId) {
            const logElement = document.querySelector(`[data-log-id="${logId}"]`);
            if (logElement) {
                logElement.classList.add('slide-out');
                setTimeout(() => {
                    logs = logs.filter(log => log.id !== logId);
                    applyFilters();
                    renderLogs();
                }, 300);
            }
        }
        
        // Auto-reconnect on page visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && isStreamActive && (!eventSource || eventSource.readyState !== EventSource.OPEN)) {
                connectToStream();
            }
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>
```

---

## 🛠️ Commands & Tools

### 📋 Available Commands

<details>
<summary>🔧 Installation & Setup Commands</summary>

```bash
# Run the complete installation wizard
php artisan log-management:install

# Install with custom options
php artisan log-management:install --force --skip-migrations

# Publish configuration files only
php artisan vendor:publish --tag=log-management-config

# Publish views for customization
php artisan vendor:publish --tag=log-management-views

# Run migrations manually
php artisan migrate --path=vendor/fulgid/log-management/database/migrations
```

</details>

<details>
<summary>🧪 Testing & Debugging Commands</summary>

```bash
# Test all notification channels
php artisan log-management:test --all

# Test specific notification channel
php artisan log-management:test --channel=slack
php artisan log-management:test --channel=email
php artisan log-management:test --channel=webhook

# Generate test logs for development
php artisan log-management:generate-test-logs --count=100 --level=error

# Check system health and configuration
php artisan log-management:health

# Debug configuration issues
php artisan log-management:debug --verbose
```

</details>

<details>
<summary>🗄️ Database Management Commands</summary>

```bash
# Clean up old log entries
php artisan log-management:cleanup --days=30

# Clean up specific log levels
php artisan log-management:cleanup --level=debug --days=7

# Optimize database performance
php artisan log-management:optimize

# Backup log data
php artisan log-management:backup --path=/backups/logs

# Restore log data
php artisan log-management:restore --file=/backups/logs/backup.sql
```

</details>

<details>
<summary>📊 Statistics & Reports</summary>

```bash
# Generate log statistics report
php artisan log-management:stats --period=7d --format=table

# Export logs to various formats
php artisan log-management:export --format=csv --from=2024-01-01 --to=2024-01-31
php artisan log-management:export --format=json --level=error
php artisan log-management:export --format=excel --user-id=123

# Generate trending analysis
php artisan log-management:trends --period=30d --group-by=level

# Performance analysis
php artisan log-management:performance --analyze-slow-queries
```

</details>

<details>
<summary>🔐 Security & Maintenance</summary>

```bash
# Rotate API keys
php artisan log-management:rotate-keys --backup-old

# Verify security configuration
php artisan log-management:security-check

# Update package configurations
php artisan log-management:update-config

# Clear all caches
php artisan log-management:clear-cache
```

</details>

### 🎯 Command Examples

```bash
# Complete health check with detailed output
php artisan log-management:health --detailed

# Generate 1000 test logs for load testing
php artisan log-management:generate-test-logs \
    --count=1000 \
    --levels=error,warning,info \
    --with-context \
    --simulate-users

# Export last week's error logs as Excel
php artisan log-management:export \
    --format=excel \
    --level=error \
    --from="1 week ago" \
    --to=today \
    --output=/reports/weekly-errors.xlsx

# Test Slack integration with custom message
php artisan log-management:test \
    --channel=slack \
    --message="🧪 Testing from production server" \
    --level=warning

# Clean up debug logs older than 3 days
php artisan log-management:cleanup \
    --level=debug \
    --days=3 \
    --confirm \
    --verbose
```

---

## 🚀 Deployment

### 🐳 Docker Deployment

<details>
<summary>📦 Docker Configuration</summary>

```dockerfile
# Dockerfile
FROM php:8.2-fpm-alpine

# Install required extensions
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    && docker-php-ext-install pdo pdo_mysql

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "80:80"
    environment:
      - LOG_MANAGEMENT_ENABLED=true
      - LOG_MANAGEMENT_ENVIRONMENTS=production
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - DB_DATABASE=laravel
      - DB_USERNAME=laravel
      - DB_PASSWORD=secret
      - REDIS_HOST=redis
    volumes:
      - ./storage/logs:/var/www/html/storage/logs
    depends_on:
      - db
      - redis
    restart: unless-stopped

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: laravel
      MYSQL_USER: laravel
      MYSQL_PASSWORD: secret
    volumes:
      - mysql_data:/var/lib/mysql
    restart: unless-stopped

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    restart: unless-stopped

  queue:
    build: .
    command: php artisan queue:work --sleep=3 --tries=3
    environment:
      - LOG_MANAGEMENT_ENABLED=true
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - REDIS_HOST=redis
    depends_on:
      - db
      - redis
    restart: unless-stopped

volumes:
  mysql_data:
  redis_data:
```

</details>

### ☸️ Kubernetes Deployment

<details>
<summary>⚙️ Kubernetes Manifests</summary>

```yaml
# k8s/configmap.yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: log-management-config
data:
  .env: |
    LOG_MANAGEMENT_ENABLED=true
    LOG_MANAGEMENT_ENVIRONMENTS=production
    LOG_MANAGEMENT_SSE_ENABLED=true
    LOG_MANAGEMENT_RATE_LIMIT_MAX=100
    LOG_MANAGEMENT_RETENTION_DAYS=30

---
# k8s/deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: log-management-app
spec:
  replicas: 3
  selector:
    matchLabels:
      app: log-management
  template:
    metadata:
      labels:
        app: log-management
    spec:
      containers:
      - name: app
        image: your-registry/log-management:latest
        ports:
        - containerPort: 80
        env:
        - name: LOG_MANAGEMENT_API_KEY_1
          valueFrom:
            secretKeyRef:
              name: log-management-secrets
              key: api-key-1
        volumeMounts:
        - name: config
          mountPath: /var/www/html/.env
          subPath: .env
        - name: storage
          mountPath: /var/www/html/storage
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
      volumes:
      - name: config
        configMap:
          name: log-management-config
      - name: storage
        persistentVolumeClaim:
          claimName: log-management-storage

---
# k8s/service.yaml
apiVersion: v1
kind: Service
metadata:
  name: log-management-service
spec:
  selector:
    app: log-management
  ports:
  - port: 80
    targetPort: 80
  type: ClusterIP

---
# k8s/ingress.yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: log-management-ingress
  annotations:
    nginx.ingress.kubernetes.io/rewrite-target: /
    cert-manager.io/cluster-issuer: letsencrypt-prod
spec:
  tls:
  - hosts:
    - logs.yourapp.com
    secretName: log-management-tls
  rules:
  - host: logs.yourapp.com
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: log-management-service
            port:
              number: 80
```

</details>

### 🚀 Production Optimizations

<details>
<summary>⚡ Performance Optimizations</summary>

```bash
# Optimize Composer autoloader
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize for production
php artisan optimize

# Set up log rotation
echo "*/5 * * * * php /var/www/html/artisan log-management:cleanup --days=30" | crontab -

# Configure Redis for sessions and cache
# In .env:
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

```nginx
# nginx.conf optimization
server {
    listen 80;
    server_name logs.yourapp.com;
    root /var/www/html/public;
    index index.php;

    # Enable gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # SSE specific configuration
    location /log-management/stream {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_buffering off;
        proxy_read_timeout 86400;
    }

    # Static assets caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP processing
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
}
```

</details>

---

## 🔍 Troubleshooting

### 🚨 Common Issues

<details>
<summary>🔧 Installation Issues</summary>

**Issue**: Package installation fails with dependency conflicts

```bash
# Solution: Update Composer and try again
composer self-update
composer update --with-dependencies

# Or install with specific Laravel version
composer require fulgid/log-management --with-laravel=^10.0
```

**Issue**: Migration fails with table already exists error

```bash
# Solution: Check and rollback if necessary
php artisan migrate:status
php artisan migrate:rollback --step=1
php artisan log-management:install --skip-migrations
```

**Issue**: API keys not generating during installation

```bash
# Solution: Generate manually
php artisan log-management:rotate-keys --force
```

</details>

<details>
<summary>📡 Streaming Issues</summary>

**Issue**: SSE connection keeps dropping

```javascript
// Solution: Implement robust reconnection logic
function connectWithRetry(maxRetries = 5) {
    let retryCount = 0;
    
    function connect() {
        const eventSource = new EventSource('/log-management/stream?key=' + API_KEY);
        
        eventSource.onerror = function() {
            eventSource.close();
            
            if (retryCount < maxRetries) {
                retryCount++;
                const delay = Math.pow(2, retryCount) * 1000; // Exponential backoff
                setTimeout(connect, delay);
            }
        };
        
        return eventSource;
    }
    
    return connect();
}
```

**Issue**: High memory usage with many connections

```env
# Solution: Adjust SSE settings
LOG_MANAGEMENT_SSE_MAX_CONNECTIONS=50
LOG_MANAGEMENT_SSE_BUFFER_SIZE=512
LOG_MANAGEMENT_SSE_HEARTBEAT_INTERVAL=60
```

</details>

<details>
<summary>🔔 Notification Issues</summary>

**Issue**: Slack notifications not working

```bash
# Debug Slack webhook
php artisan log-management:test --channel=slack --debug

# Check webhook URL format
# Correct: https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX
# Incorrect: https://hooks.slack.com/workflows/...
```

**Issue**: Email notifications marked as spam

```env
# Solution: Configure SPF, DKIM, and DMARC records
# Use a reputable email service like SendGrid or Mailgun
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
```

**Issue**: Rate limiting blocking notifications

```env
# Solution: Adjust rate limits or implement smarter throttling
LOG_MANAGEMENT_RATE_LIMIT_MAX=100
LOG_MANAGEMENT_RATE_LIMIT_WINDOW=60

# Or disable for critical errors
LOG_MANAGEMENT_RATE_LIMIT_BYPASS_LEVELS=emergency,alert,critical
```

</details>

<details>
<summary>📊 Performance Issues</summary>

**Issue**: Dashboard loading slowly

```bash
# Solution: Optimize database queries
php artisan log-management:optimize

# Add database indexes
php artisan make:migration add_indexes_to_logs_table
```

```php
// In migration
Schema::table('log_management_logs', function (Blueprint $table) {
    $table->index(['level', 'created_at']);
    $table->index(['user_id', 'created_at']);
    $table->index('channel');
});
```

**Issue**: High CPU usage during log processing

```env
# Solution: Enable async processing and increase batch size
LOG_MANAGEMENT_ASYNC_PROCESSING=true
LOG_MANAGEMENT_BATCH_SIZE=500
LOG_MANAGEMENT_MEMORY_LIMIT=512M

# Use Redis for queues
QUEUE_CONNECTION=redis
```

**Issue**: Database growing too large

```bash
# Solution: Implement aggressive cleanup and archiving
php artisan log-management:cleanup --days=7 --level=debug
php artisan log-management:cleanup --days=30 --exclude-levels=error,critical

# Set up automated cleanup
*/0 2 * * * php /var/www/html/artisan log-management:cleanup --days=30 --quiet
```

</details>

### 🛠️ Debug Mode

Enable comprehensive debugging:

```env
LOG_MANAGEMENT_DEBUG=true
LOG_MANAGEMENT_DEBUG_QUERIES=true
LOG_MANAGEMENT_DEBUG_NOTIFICATIONS=true
```

```bash
# View debug logs
tail -f storage/logs/log-management-debug.log

# Get detailed system information
php artisan log-management:debug --system-info

# Test all components
php artisan log-management:health --comprehensive
```

### 📞 Getting Help

**Community Support:**
- 💬 [GitHub Discussions](https://github.com/navinnm/log-management/discussions)
- 🐛 [Bug Reports](https://github.com/navinnm/log-management/issues)
<!-- - 📖 [Documentation](https://docs.fulgid.com/log-management) -->

**Professional Support:**
- 📧 Email: support@fulgid.in
- 💼 Enterprise: enterprise@fulgid.in
- 🎯 Custom Development: dev@fulgid.in

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### 🛠️ Development Setup

```bash
# Clone the repository
git clone https://github.com/navinnm/log-management.git
cd log-management

# Install dependencies
composer install
npm install

# Set up test environment
cp .env.testing.example .env.testing
php artisan key:generate --env=testing

# Run tests
vendor/bin/phpunit
npm run test
```

### 📋 Contribution Guidelines

<details>
<summary>🎯 Code Standards</summary>

```bash
# Format code with PHP CS Fixer
vendor/bin/php-cs-fixer fix

# Run static analysis with PHPStan
vendor/bin/phpstan analyse

# Check code quality with PHPMD
vendor/bin/phpmd src text cleancode,codesize,controversial,design,naming,unusedcode
```

**Code Style Requirements:**
- Follow PSR-12 coding standards
- Write comprehensive PHPDoc comments
- Include unit tests for new features
- Maintain backward compatibility
- Use semantic versioning for releases

</details>

<details>
<summary>🧪 Testing Requirements</summary>

```php
// Example test case
class LogManagementTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_can_store_log_entries()
    {
        // Arrange
        $logData = [
            'level' => 'error',
            'message' => 'Test error message',
            'context' => ['user_id' => 123]
        ];
        
        // Act
        LogManagement::log($logData);
        
        // Assert
        $this->assertDatabaseHas('log_management_logs', [
            'level' => 'error',
            'message' => 'Test error message'
        ]);
    }
    
    /** @test */
    public function it_sends_notifications_for_critical_errors()
    {
        // Arrange
        Notification::fake();
        
        // Act
        LogManagement::log([
            'level' => 'critical',
            'message' => 'Critical system error'
        ]);
        
        // Assert
        Notification::assertSent(CriticalErrorNotification::class);
    }
}
```

**Testing Checklist:**
- ✅ Unit tests for all new methods
- ✅ Integration tests for complex features
- ✅ Browser tests for dashboard functionality
- ✅ Performance tests for high-load scenarios
- ✅ Security tests for API endpoints

</details>

### 🔄 Pull Request Process

1. **Fork** the repository
2. **Create** a feature branch (`git checkout -b feature/amazing-feature`)
3. **Write** tests for your changes
4. **Ensure** all tests pass (`composer test`)
5. **Commit** your changes (`git commit -m 'Add amazing feature'`)
6. **Push** to the branch (`git push origin feature/amazing-feature`)
7. **Open** a Pull Request

### 🎯 Areas for Contribution

**High Priority:**
- 🔧 Additional notification channels (Teams, Telegram, etc.)
- 📊 Advanced analytics and reporting features
- 🎨 Dashboard themes and customization options
- 🌐 Internationalization (i18n) support
- 📱 Mobile app for log monitoring

**Medium Priority:**
- 🔍 Advanced search with Elasticsearch integration
- 📈 Machine learning for anomaly detection
- 🔐 Advanced security features (2FA, SSO)
- 📊 Custom chart types and visualizations
- 🚀 Performance optimizations

**Documentation:**
- 📝 Tutorial videos and guides
- 🌍 Translation of documentation
- 💡 Usage examples and recipes
- 🏗️ Architecture deep-dives
- 🎓 Best practices guides

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

### 🏢 Commercial License

For commercial projects requiring additional features, support, or custom development:

**Pro License Features:**
- 🚀 Priority email support
- 📊 Advanced analytics dashboard
- 🔒 Enhanced security features
- 🎨 White-label customization
- 📱 Mobile companion app

**Enterprise License Features:**
- 📞 Phone support & SLA guarantees
- 🏗️ Custom integrations development
- 🎓 Team training sessions
- 🔧 On-premise deployment assistance
- 📊 Custom reporting & analytics

Contact **enterprise@fulgid.com** for licensing information.

---
<!-- 
## 📚 Additional Resources

### 🎓 Learning Resources

**Tutorials:**
- [Getting Started with Log Management](https://docs.fulgid.com/tutorials/getting-started)
- [Advanced Configuration Guide](https://docs.fulgid.com/tutorials/advanced-config)
- [Building Custom Notification Channels](https://docs.fulgid.com/tutorials/custom-channels)
- [Performance Optimization Tips](https://docs.fulgid.com/tutorials/performance)

**Video Courses:**
- 🎬 [Complete Log Management Course](https://learn.fulgid.com/log-management)
- 🎬 [Real-time Monitoring Masterclass](https://learn.fulgid.com/monitoring)
- 🎬 [Laravel Logging Best Practices](https://learn.fulgid.com/laravel-logging) -->

<!-- ### 🔗 Related Packages

**Ecosystem:**
- 📊 [Laravel Metrics](https://github.com/navinnm/laravel-metrics) - Application performance monitoring
- 🔍 [Laravel Search](https://github.com/fulgid/laravel-search) - Advanced search capabilities
- 📧 [Laravel Notifications+](https://github.com/fulgid/laravel-notifications-plus) - Enhanced notification system
- 🛡️ [Laravel Security](https://github.com/fulgid/laravel-security) - Security monitoring and alerts -->

**Integrations:**
- 🔭 Laravel Telescope
- 📊 Laravel Horizon
- 🐛 Bugsnag
- 📈 New Relic
- 🔍 Elasticsearch
- 📊 Grafana

### 💡 Tips & Tricks

<details>
<summary>🚀 Performance Tips</summary>

```php
// Use log contexts for better filtering
Log::error('Payment failed', [
    'user_id' => $user->id,
    'payment_id' => $payment->id,
    'gateway' => 'stripe',
    'amount' => $payment->amount
]);

// Batch log operations for better performance
LogManagement::batch([
    ['level' => 'info', 'message' => 'Batch operation 1'],
    ['level' => 'info', 'message' => 'Batch operation 2'],
    ['level' => 'info', 'message' => 'Batch operation 3'],
]);

// Use conditional logging to reduce noise
if (app()->environment('production')) {
    Log::channel('log-management')->info('Production-only log');
}
```

</details>

<details>
<summary>🔧 Advanced Configuration</summary>

```php
// Custom log processor
LogManagement::addProcessor(function ($record) {
    $record['extra']['server_id'] = gethostname();
    $record['extra']['memory_usage'] = memory_get_usage(true);
    return $record;
});

// Custom alert rules
LogManagement::addAlertRule('high_error_rate', function ($stats) {
    return $stats['error_rate'] > 5.0; // Alert if error rate > 5%
});

// Dynamic notification routing
LogManagement::addNotificationRouter(function ($logData) {
    if ($logData['level'] === 'critical') {
        return ['slack', 'email', 'webhook'];
    } elseif ($logData['level'] === 'error') {
        return ['slack'];
    }
    return [];
});
```

</details>

---

## 🎉 Conclusion

Thank you for choosing **Laravel Log Management**! This package represents hundreds of hours of development focused on providing the best possible logging experience for Laravel applications.

### 🚀 What's Next?

1. **Get Started**: Follow our [Quick Start Guide](#-quick-start) to get up and running in minutes
2. **Customize**: Explore our [Configuration Options](#️-configuration) to tailor the package to your needs
3. **Integrate**: Set up [Real-time Streaming](#-real-time-streaming) for live monitoring
4. **Optimize**: Implement [Performance Best Practices](#-deployment) for production
5. **Contribute**: Join our [Community](#-contributing) and help make the package even better

### 💬 Stay Connected

- 🐦 Follow us on [Twitter](https://twitter.com/fulgidinc)
- 💼 Connect on [LinkedIn](https://linkedin.com/company/fulgidinc)
- 📧 Subscribe to our [Newsletter](https://fulgid.in/newsletter)
- 🗣️ Join our [Discord Community](https://discord.gg/fulgid)

### ⭐ Show Your Support

If this package helps you build better applications, please consider:
- ⭐ Starring the [GitHub repository](https://github.com/fulgid/log-management)
- 🐦 Sharing on social media
- 📝 Writing a blog post about your experience
- 💬 Recommending to colleagues

---

<div align="center">

**Built with ❤️ by the Fulgid Team**

[🌐 Website](https://fulgid.in) • [📧 Contact](mailto:ping@fulgid.in) • [🐙 GitHub](https://github.com/fulgid)

*Making Laravel development more enjoyable, one package at a time.*

</div>