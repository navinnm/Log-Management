<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log Management Dashboard - {{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
        
        :root {
            --bg-primary: #0a0a0b;
            --bg-secondary: #141517;
            --bg-tertiary: #1c1d20;
            --bg-elevated: #24252a;
            --border-color: #2a2b30;
            --border-active: #3a3b42;
            --text-primary: #ffffff;
            --text-secondary: #a0a1a7;
            --text-tertiary: #6c6d75;
            --accent-blue: #0066ff;
            --accent-green: #00c851;
            --accent-red: #ff4444;
            --accent-orange: #ff8800;
            --accent-yellow: #ffcc02;
            --glow-blue: rgba(0, 102, 255, 0.15);
            --glow-red: rgba(255, 68, 68, 0.15);
            --glow-green: rgba(0, 200, 81, 0.15);
            --radius: 8px;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Layout */
        .container {
            display: grid;
            grid-template-columns: 260px 1fr;
            grid-template-rows: 60px 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
            min-height: 100vh;
        }

        /* Header */
        .header {
            grid-area: header;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }

        .search-box {
            position: relative;
            width: 280px;
        }

        .search-input {
            width: 100%;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 8px 12px 8px 36px;
            color: var(--text-primary);
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 2px var(--glow-blue);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            font-size: 13px;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-red);
            animation: pulse 2s infinite;
        }

        .status-dot.connected {
            background: var(--accent-green);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .btn {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-primary);
            padding: 8px 12px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: var(--bg-elevated);
            border-color: var(--border-active);
        }

        .btn.active {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 20px;
            overflow-y: auto;
        }

        .section {
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
        }

        .filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: var(--radius);
            cursor: pointer;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }

        .filter-item:hover {
            background: var(--bg-tertiary);
        }

        .filter-item.active {
            background: var(--bg-elevated);
            border: 1px solid var(--accent-blue);
        }

        .filter-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .level-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }

        .level-emergency { background: var(--accent-red); }
        .level-alert { background: var(--accent-orange); }
        .level-critical { background: #ff2d92; }
        .level-error { background: var(--accent-red); }
        .level-warning { background: var(--accent-yellow); }
        .level-notice { background: var(--accent-blue); }
        .level-info { background: var(--accent-green); }
        .level-debug { background: var(--text-tertiary); }

        .count {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
        }

        .filter-item.active .count {
            background: var(--accent-blue);
            color: white;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: var(--bg-elevated);
            border-color: var(--border-active);
        }

        /* Main Content */
        .main {
            grid-area: main;
            padding: 20px;
            overflow-y: auto;
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 16px;
            transition: all 0.2s ease;
        }

        .stat-card:hover {
            border-color: var(--border-active);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* Logs */
        .logs-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .logs-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logs-title {
            font-size: 16px;
            font-weight: 600;
        }

        .logs-controls {
            display: flex;
            gap: 8px;
        }

        .logs-container {
            max-height: 600px;
            overflow-y: auto;
        }

        .logs-container::-webkit-scrollbar {
            width: 4px;
        }

        .logs-container::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        .logs-container::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }

        .log-entry {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .log-entry:hover {
            background: var(--bg-tertiary);
        }

        .log-entry.new {
            animation: highlight 2s ease-out;
        }

        @keyframes highlight {
            0% {
                background: var(--glow-blue);
            }
            100% {
                background: transparent;
            }
        }

        .log-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 6px;
        }

        .log-level {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            min-width: 50px;
            text-align: center;
        }

        .log-level.emergency,
        .log-level.alert,
        .log-level.critical {
            background: var(--accent-red);
            color: white;
        }

        .log-level.error {
            background: rgba(255, 68, 68, 0.2);
            color: var(--accent-red);
            border: 1px solid var(--accent-red);
        }

        .log-level.warning {
            background: rgba(255, 204, 2, 0.2);
            color: var(--accent-yellow);
            border: 1px solid var(--accent-yellow);
        }

        .log-level.notice,
        .log-level.info {
            background: rgba(0, 102, 255, 0.2);
            color: var(--accent-blue);
            border: 1px solid var(--accent-blue);
        }

        .log-level.debug {
            background: rgba(108, 109, 117, 0.2);
            color: var(--text-tertiary);
            border: 1px solid var(--text-tertiary);
        }

        .log-time {
            color: var(--text-tertiary);
            font-size: 11px;
            font-family: 'JetBrains Mono', monospace;
        }

        .log-channel {
            color: var(--text-secondary);
            font-size: 11px;
        }

        .log-message {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.4;
        }

        .log-details {
            display: none;
            margin-top: 12px;
            padding: 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--text-secondary);
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
        }

        .log-entry.expanded .log-details {
            display: block;
        }

        /* Error Beautification */
        .error-container {
            background: var(--bg-primary);
            border: 2px solid var(--accent-red);
            border-radius: var(--radius);
            margin: 12px 0;
            overflow: hidden;
        }

        .error-header {
            background: var(--accent-red);
            color: white;
            padding: 12px 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-content {
            padding: 16px;
        }

        .error-file {
            color: var(--accent-blue);
            font-weight: 600;
            margin-bottom: 8px;
        }

        .error-line {
            color: var(--accent-yellow);
            font-weight: 500;
        }

        .error-message {
            color: var(--accent-red);
            font-weight: 600;
            margin: 12px 0;
            padding: 8px 12px;
            background: rgba(255, 68, 68, 0.1);
            border-radius: 4px;
            border-left: 4px solid var(--accent-red);
        }

        .stack-trace {
            margin-top: 16px;
        }

        .stack-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--border-color);
        }

        .stack-item {
            margin: 8px 0;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 4px;
            border-left: 3px solid var(--accent-orange);
        }

        .stack-function {
            color: var(--accent-green);
            font-weight: 600;
        }

        .stack-file {
            color: var(--accent-blue);
            font-size: 10px;
            margin-top: 4px;
        }

        .stack-line {
            color: var(--accent-yellow);
        }

        .code-snippet {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin: 8px 0;
            overflow-x: auto;
        }

        .code-line {
            padding: 2px 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            display: flex;
            align-items: center;
        }

        .code-line.error-line {
            background: rgba(255, 68, 68, 0.1);
            border-left: 4px solid var(--accent-red);
        }

        .line-number {
            color: var(--text-tertiary);
            width: 40px;
            text-align: right;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .line-content {
            color: var(--text-secondary);
            flex: 1;
        }

        /* JSON Syntax Highlighting */
        .json-container {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            overflow-x: auto;
        }

        .json-key {
            color: var(--accent-blue);
        }

        .json-string {
            color: var(--accent-green);
        }

        .json-number {
            color: var(--accent-orange);
        }

        .json-boolean {
            color: var(--accent-yellow);
        }

        .json-null {
            color: var(--text-tertiary);
        }

        /* Log Type Indicators */
        .log-type-error .log-details {
            border-left: 4px solid var(--accent-red);
        }

        .log-type-warning .log-details {
            border-left: 4px solid var(--accent-yellow);
        }

        .log-type-info .log-details {
            border-left: 4px solid var(--accent-blue);
        }

        .log-type-debug .log-details {
            border-left: 4px solid var(--text-tertiary);
        }

        /* Empty State */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-tertiary);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast {
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 12px 16px;
            min-width: 250px;
            box-shadow: var(--shadow);
            animation: toast-in 0.3s ease-out;
            font-size: 13px;
        }

        .toast.success {
            border-color: var(--accent-green);
        }

        .toast.error {
            border-color: var(--accent-red);
        }

        @keyframes toast-in {
            0% {
                opacity: 0;
                transform: translateX(100%);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }
            
            .sidebar {
                display: none;
            }
            
            .search-box {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="toast-container" id="toastContainer"></div>

    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Search logs..." id="searchInput">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div class="header-actions">
                <div class="status">
                    <div class="status-dot" id="connectionStatus"></div>
                    <span id="connectionText">Connecting...</span>
                </div>
                <button class="btn active" id="autoScrollBtn">
                    <i class="fas fa-arrows-alt-v"></i>
                </button>
                <button class="btn" id="pauseBtn">
                    <i class="fas fa-pause"></i>
                </button>
                <button class="btn" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="section">
                <h3 class="section-title">Levels</h3>
                <div id="levelFilters">
                    <div class="filter-item active" data-level="all">
                        <div class="filter-label">
                            <div class="level-dot" style="background: var(--accent-blue);"></div>
                            All
                        </div>
                        <span class="count" id="count-all">0</span>
                    </div>
                    <div class="filter-item" data-level="emergency">
                        <div class="filter-label">
                            <div class="level-dot level-emergency"></div>
                            Emergency
                        </div>
                        <span class="count" id="count-emergency">0</span>
                    </div>
                    <div class="filter-item" data-level="alert">
                        <div class="filter-label">
                            <div class="level-dot level-alert"></div>
                            Alert
                        </div>
                        <span class="count" id="count-alert">0</span>
                    </div>
                    <div class="filter-item" data-level="critical">
                        <div class="filter-label">
                            <div class="level-dot level-critical"></div>
                            Critical
                        </div>
                        <span class="count" id="count-critical">0</span>
                    </div>
                    <div class="filter-item" data-level="error">
                        <div class="filter-label">
                            <div class="level-dot level-error"></div>
                            Error
                        </div>
                        <span class="count" id="count-error">0</span>
                    </div>
                    <div class="filter-item" data-level="warning">
                        <div class="filter-label">
                            <div class="level-dot level-warning"></div>
                            Warning
                        </div>
                        <span class="count" id="count-warning">0</span>
                    </div>
                    <div class="filter-item" data-level="notice">
                        <div class="filter-label">
                            <div class="level-dot level-notice"></div>
                            Notice
                        </div>
                        <span class="count" id="count-notice">0</span>
                    </div>
                    <div class="filter-item" data-level="info">
                        <div class="filter-label">
                            <div class="level-dot level-info"></div>
                            Info
                        </div>
                        <span class="count" id="count-info">0</span>
                    </div>
                    <div class="filter-item" data-level="debug">
                        <div class="filter-label">
                            <div class="level-dot level-debug"></div>
                            Debug
                        </div>
                        <span class="count" id="count-debug">0</span>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3 class="section-title">Actions</h3>
                <button class="action-btn" id="clearLogsBtn">
                    <i class="fas fa-trash"></i>
                    Clear Logs
                </button>
                <button class="action-btn" id="exportLogsBtn">
                    <i class="fas fa-download"></i>
                    Export
                </button>
                <button class="action-btn" id="testNotificationBtn">
                    <i class="fas fa-bell"></i>
                    Test Alert
                </button>
                <button class="action-btn" id="loadMoreBtn">
                    <i class="fas fa-plus"></i>
                    Load More
                </button>
            </div>
        </aside>

        <!-- Main -->
        <main class="main">
            <!-- Stats -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value" id="totalLogs">{{ number_format($stats['total_logs']) }}</div>
                    <div class="stat-label">Total Logs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="todayLogs">{{ number_format($stats['logs_today']) }}</div>
                    <div class="stat-label">Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="hourLogs">{{ number_format($stats['logs_last_hour']) }}</div>
                    <div class="stat-label">Last Hour</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="errorLogs">{{ number_format($stats['error_logs_today']) }}</div>
                    <div class="stat-label">Errors Today</div>
                </div>
            </div>

            <!-- Logs -->
            <div class="logs-section">
                <div class="logs-header">
                    <h2 class="logs-title">Live Stream</h2>
                    <div class="logs-controls">
                        <span style="font-size: 11px; color: var(--text-tertiary);">
                            <i class="fas fa-circle" style="color: var(--accent-green); font-size: 6px;"></i>
                            Live
                        </span>
                        <span style="font-size: 11px; color: var(--text-tertiary); margin-left: 12px;" id="paginationInfo">
                            Page 1 of 1
                        </span>
                        <span style="font-size: 11px; color: var(--text-tertiary); margin-left: 8px;">
                            DB: {{ $recentLogs->count() }} logs
                        </span>
                    </div>
                </div>

                <!-- Pagination Controls -->
                <div style="padding: 12px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: between; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button class="btn" id="firstPageBtn" disabled>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button class="btn" id="prevPageBtn" disabled>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <span style="font-size: 13px; color: var(--text-secondary); margin: 0 8px;" id="pageInfo">
                            Page 1 of 1
                        </span>
                        <button class="btn" id="nextPageBtn" disabled>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="btn" id="lastPageBtn" disabled>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12px; color: var(--text-tertiary);">Per page:</span>
                        <select id="perPageSelect" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-primary); padding: 4px 8px; font-size: 12px;">
                            <option value="25">25</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12px; color: var(--text-tertiary);">Go to:</span>
                        <input type="number" id="pageInput" min="1" value="1" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-primary); padding: 4px 8px; font-size: 12px; width: 60px;">
                        <button class="btn" id="goToPageBtn" style="font-size: 11px; padding: 4px 8px;">Go</button>
                    </div>
                </div>

                <div class="logs-container" id="logsContainer">
                    @if($recentLogs->count() > 0)
                        @foreach($recentLogs as $log)
                        <div class="log-entry log-type-{{ $log->level }}" data-level="{{ $log->level }}" onclick="toggleLogDetails(this)">
                            <div class="log-header">
                                <span class="log-level {{ $log->level }}">{{ strtoupper($log->level) }}</span>
                                <span class="log-time">{{ $log->created_at->format('H:i:s') }}</span>
                                <span class="log-channel">{{ $log->channel ?: 'app' }}</span>
                            </div>
                            <div class="log-message">{{ $log->message }}</div>
                            @if($log->context || $log->stack_trace || $log->url)
                            <div class="log-details">
                                @if($log->context)
                                    @php
                                        $context = is_string($log->context) ? json_decode($log->context, true) : $log->context;
                                    @endphp
                                    @if(isset($context['exception']))
                                        <div class="error-container">
                                            <div class="error-header">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ $context['exception']['class'] ?? 'Exception' }}
                                            </div>
                                            <div class="error-content">
                                                <div class="error-message">
                                                    {{ $context['exception']['message'] ?? 'Unknown error' }}
                                                </div>
                                                @if(isset($context['exception']['file']))
                                                    <div class="error-file">
                                                        <i class="fas fa-file-code"></i>
                                                        {{ $context['exception']['file'] }}
                                                        @if(isset($context['exception']['line']))
                                                            <span class="error-line">:{{ $context['exception']['line'] }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="json-container">
{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}
                                        </div>
                                    @endif
                                @endif
                                @if($log->stack_trace)
                                    <div class="stack-trace">
                                        <div class="stack-title">
                                            <i class="fas fa-layer-group"></i> Stack Trace
                                        </div>
                                        <div style="font-family: 'JetBrains Mono', monospace; font-size: 10px; color: var(--text-secondary); white-space: pre-wrap; max-height: 300px; overflow-y: auto;">{{ $log->stack_trace }}</div>
                                    </div>
                                @endif
                                @if($log->url)
                                    <div style="margin-top: 12px; padding: 8px 12px; background: rgba(0, 102, 255, 0.1); border-radius: 4px; border-left: 4px solid var(--accent-blue);">
                                        <strong style="color: var(--accent-blue);">Request URL:</strong><br>
                                        <span style="color: var(--text-primary); font-family: monospace;">{{ $log->url }}</span>
                                    </div>
                                @endif
                            </div>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <div>No logs found in database</div>
                            <div style="font-size: 11px; color: var(--text-tertiary); margin-top: 8px;">
                                Total in DB: {{ \Fulgid\LogManagement\Models\LogEntry::count() }} logs
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Bottom Pagination -->
                <div style="padding: 12px 20px; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: between; gap: 12px;">
                    <span style="font-size: 12px; color: var(--text-tertiary);" id="totalInfo">
                        Showing 1 to {{ $recentLogs->count() }} of {{ \Fulgid\LogManagement\Models\LogEntry::count() }} logs
                    </span>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button class="btn" id="firstPageBtn2" disabled>
                            <i class="fas fa-angle-double-left"></i>
                        </button>
                        <button class="btn" id="prevPageBtn2" disabled>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <button class="btn" id="nextPageBtn2" disabled>
                            <i class="fas fa-angle-right"></i>
                        </button>
                        <button class="btn" id="lastPageBtn2" disabled>
                            <i class="fas fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Powered By Footer -->
                <div style="padding: 8px 20px; border-top: 1px solid var(--border-color); background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 11px; color: var(--text-tertiary); display: flex; align-items: center; gap: 4px;">
                        <i class="fas fa-code" style="font-size: 10px;"></i>
                        Powered by 
                        <strong style="color: var(--accent-blue); font-weight: 600;">Fulgid Software Solutions Pvt Ltd</strong>
                    </span>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Configuration
        const API_KEY = '{{ config("log-management.auth.api_keys.0") ?: env("LOG_MANAGEMENT_API_KEY_1") }}';
        const BASE_URL = '{{ url("/log-management") }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        
        // State
        let eventSource = null;
        let isConnected = false;
        let isPaused = false;
        let autoScroll = true;
        let currentFilter = 'all';
        let searchTerm = '';
        let levelCounts = {};
        
        // Pagination state
        let currentPage = 1;
        let perPage = 50;
        let totalPages = 1;
        let totalLogs = 0;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            connectToStream();
            setupEventListeners();
        });

        function initializeDashboard() {
            console.log('Initializing dashboard...');
            updateConnectionStatus('connecting');
            
            // Initialize counts from server data
            const serverLevelStats = @json($levelStats);
            updateFilterCounts(serverLevelStats);
        }

        function connectToStream() {
            if (eventSource) {
                eventSource.close();
            }

            const streamUrl = `${BASE_URL}/stream?key=${API_KEY}&include_recent=false`;
            console.log('Connecting to stream...');

            eventSource = new EventSource(streamUrl);

            eventSource.onopen = function(event) {
                console.log('Stream connected');
                updateConnectionStatus('connected');
                showToast('Connected to live stream', 'success');
            };

            eventSource.onmessage = function(event) {
                handleStreamMessage(event);
            };

            eventSource.onerror = function(event) {
                console.error('Stream error:', event);
                updateConnectionStatus('disconnected');
                
                if (!isPaused) {
                    setTimeout(() => {
                        console.log('Reconnecting...');
                        connectToStream();
                    }, 5000);
                }
            };

            // Handle specific event types
            ['connection', 'log', 'heartbeat', 'statistics', 'error'].forEach(eventType => {
                eventSource.addEventListener(eventType, function(event) {
                    handleStreamMessage(event, eventType);
                });
            });
        }

        function handleStreamMessage(event, eventType = 'message') {
            if (isPaused) return;

            try {
                const data = JSON.parse(event.data);
                
                switch (data.type || eventType) {
                    case 'connected':
                    case 'connection':
                        updateConnectionStatus('connected');
                        break;

                    case 'log':
                        addLogEntry(data);
                        updateCounts();
                        break;

                    case 'heartbeat':
                        updateConnectionStatus('connected');
                        break;

                    case 'statistics':
                        updateStatistics(data);
                        break;

                    case 'error':
                        console.error('Stream error:', data);
                        showToast(data.message || 'Stream error occurred', 'error');
                        break;
                }
            } catch (error) {
                console.error('Failed to parse stream data:', error);
            }
        }

        function addLogEntry(logData) {
            const container = document.getElementById('logsContainer');
            
            // Remove empty state if present
            const emptyState = container.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }

            const entry = createLogEntryElement(logData);
            container.insertBefore(entry, container.firstChild);

            // Highlight new entry
            entry.classList.add('new');
            setTimeout(() => entry.classList.remove('new'), 2000);

            // Auto scroll if enabled
            if (autoScroll) {
                entry.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Limit entries for performance
            const logs = container.querySelectorAll('.log-entry');
            if (logs.length > 500) {
                for (let i = 400; i < logs.length; i++) {
                    logs[i].remove();
                }
            }

            // Apply current filter
            applyFilters();
        }

        function createLogEntryElement(logData) {
            const entry = document.createElement('div');
            entry.className = `log-entry log-type-${logData.level}`;
            entry.setAttribute('data-level', logData.level);
            entry.onclick = () => toggleLogDetails(entry);

            const timestamp = new Date(logData.timestamp || logData.created_at).toLocaleTimeString();
            
            let contextDetails = '';
            if ((logData.context && Object.keys(logData.context).length > 0) || logData.url || logData.stack_trace) {
                contextDetails = '<div class="log-details">';
                
                // Handle Laravel error formatting
                if (logData.context && logData.context.exception) {
                    contextDetails += formatLaravelError(logData.context);
                } else if (logData.stack_trace) {
                    contextDetails += formatStackTrace(logData.stack_trace);
                } else if (logData.context && Object.keys(logData.context).length > 0) {
                    contextDetails += formatJSON(logData.context);
                }
                
                if (logData.url) {
                    contextDetails += `<div style="margin-top: 12px; padding: 8px 12px; background: rgba(0, 102, 255, 0.1); border-radius: 4px; border-left: 4px solid var(--accent-blue);">
                        <strong style="color: var(--accent-blue);">Request URL:</strong><br>
                        <span style="color: var(--text-primary); font-family: monospace;">${escapeHtml(logData.url)}</span>
                    </div>`;
                }
                
                contextDetails += '</div>';
            }
            
            entry.innerHTML = `
                <div class="log-header">
                    <span class="log-level ${logData.level}">${logData.level.toUpperCase()}</span>
                    <span class="log-time">${timestamp}</span>
                    <span class="log-channel">${logData.channel || 'app'}</span>
                </div>
                <div class="log-message">${escapeHtml(logData.message)}</div>
                ${contextDetails}
            `;

            return entry;
        }

        function formatLaravelError(context) {
            const exception = context.exception;
            
            return `
                <div class="error-container">
                    <div class="error-header">
                        <i class="fas fa-exclamation-triangle"></i>
                        ${exception.class || 'Exception'}
                    </div>
                    <div class="error-content">
                        <div class="error-message">
                            ${escapeHtml(exception.message || 'Unknown error')}
                        </div>
                        ${exception.file ? `
                            <div class="error-file">
                                <i class="fas fa-file-code"></i> 
                                ${escapeHtml(exception.file)}
                                ${exception.line ? `<span class="error-line">:${exception.line}</span>` : ''}
                            </div>
                        ` : ''}
                        ${exception.trace ? formatStackTrace(exception.trace) : ''}
                    </div>
                </div>
            `;
        }

        function formatStackTrace(trace) {
            if (typeof trace === 'string') {
                // Parse string stack trace
                const lines = trace.split('\n').filter(line => line.trim());
                return `
                    <div class="stack-trace">
                        <div class="stack-title">
                            <i class="fas fa-layer-group"></i> Stack Trace
                        </div>
                        ${lines.map(line => `
                            <div class="stack-item">
                                ${formatStackTraceLine(line)}
                            </div>
                        `).join('')}
                    </div>
                `;
            } else if (Array.isArray(trace)) {
                // Parse array stack trace
                return `
                    <div class="stack-trace">
                        <div class="stack-title">
                            <i class="fas fa-layer-group"></i> Stack Trace
                        </div>
                        ${trace.map(item => `
                            <div class="stack-item">
                                <div class="stack-function">
                                    ${item.class ? `${item.class}${item.type || '::'}` : ''}${item.function || 'unknown'}()
                                </div>
                                ${item.file ? `
                                    <div class="stack-file">
                                        <i class="fas fa-file"></i> ${escapeHtml(item.file)}
                                        ${item.line ? `<span class="stack-line">:${item.line}</span>` : ''}
                                    </div>
                                ` : ''}
                            </div>
                        `).join('')}
                    </div>
                `;
            }
            return '';
        }

        function formatStackTraceLine(line) {
            // Extract function name, file, and line number from string
            const functionMatch = line.match(/([A-Za-z_\\]+(?:::|->)[A-Za-z_]+)\(/);
            const fileMatch = line.match(/([\/\w\.-]+\.php)(?:\((\d+)\))?/);
            
            let result = '';
            
            if (functionMatch) {
                result += `<div class="stack-function">${escapeHtml(functionMatch[1])}()</div>`;
            }
            
            if (fileMatch) {
                result += `<div class="stack-file">
                    <i class="fas fa-file"></i> ${escapeHtml(fileMatch[1])}
                    ${fileMatch[2] ? `<span class="stack-line">:${fileMatch[2]}</span>` : ''}
                </div>`;
            }
            
            if (!result) {
                result = `<div style="color: var(--text-secondary);">${escapeHtml(line)}</div>`;
            }
            
            return result;
        }

        function formatJSON(obj) {
            return `<div class="json-container">${syntaxHighlightJSON(obj)}</div>`;
        }

        function syntaxHighlightJSON(obj) {
            let json = JSON.stringify(obj, null, 2);
            
            // Syntax highlighting
            json = json.replace(/("([^"\\]|\\.)*")\s*:/g, '<span class="json-key">$1</span>:');
            json = json.replace(/:\s*("([^"\\]|\\.)*")/g, ': <span class="json-string">$1</span>');
            json = json.replace(/:\s*(\d+(?:\.\d+)?)/g, ': <span class="json-number">$1</span>');
            json = json.replace(/:\s*(true|false)/g, ': <span class="json-boolean">$1</span>');
            json = json.replace(/:\s*(null)/g, ': <span class="json-null">$1</span>');
            
            return json;
        }

        function updateCounts() {
            // Count visible logs by level
            const logs = document.querySelectorAll('.log-entry');
            const counts = {};
            let total = 0;

            logs.forEach(log => {
                const level = log.getAttribute('data-level');
                counts[level] = (counts[level] || 0) + 1;
                total++;
            });

            // Update sidebar counts
            ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'].forEach(level => {
                const countEl = document.getElementById(`count-${level}`);
                if (countEl) {
                    countEl.textContent = counts[level] || 0;
                }
            });

            // Update total
            const totalCountEl = document.getElementById('count-all');
            if (totalCountEl) {
                totalCountEl.textContent = total;
            }

            levelCounts = counts;
        }

        function updateFilterCounts(serverStats) {
            if (serverStats) {
                Object.entries(serverStats).forEach(([level, count]) => {
                    const countEl = document.getElementById(`count-${level}`);
                    if (countEl) {
                        countEl.textContent = count;
                    }
                });

                const total = Object.values(serverStats).reduce((sum, count) => sum + count, 0);
                const totalCountEl = document.getElementById('count-all');
                if (totalCountEl) {
                    totalCountEl.textContent = total;
                }
            }
        }

        function toggleLogDetails(element) {
            element.classList.toggle('expanded');
        }

        function updateConnectionStatus(status) {
            const statusDot = document.getElementById('connectionStatus');
            const statusText = document.getElementById('connectionText');

            statusDot.className = `status-dot ${status === 'connected' ? 'connected' : ''}`;
            statusText.textContent = status === 'connected' ? 'Connected' : 
                                   status === 'connecting' ? 'Connecting...' : 'Disconnected';
            
            isConnected = status === 'connected';
        }

        function setupEventListeners() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', debounce(function(e) {
                searchTerm = e.target.value;
                applyFilters();
            }, 300));

            // Level filter clicks
            document.getElementById('levelFilters').addEventListener('click', function(e) {
                const filterItem = e.target.closest('.filter-item');
                if (filterItem) {
                    // Update active state
                    document.querySelectorAll('#levelFilters .filter-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    filterItem.classList.add('active');

                    // Apply filter
                    currentFilter = filterItem.getAttribute('data-level');
                    
                    // Load filtered logs from server
                    loadFilteredLogs(currentFilter);
                }
            });

            // Control buttons
            document.getElementById('pauseBtn').addEventListener('click', togglePause);
            document.getElementById('autoScrollBtn').addEventListener('click', toggleAutoScroll);
            document.getElementById('refreshBtn').addEventListener('click', refreshLogs);

            // Action buttons
            document.getElementById('clearLogsBtn').addEventListener('click', clearLogs);
            document.getElementById('exportLogsBtn').addEventListener('click', exportLogs);
            document.getElementById('testNotificationBtn').addEventListener('click', testNotification);
            document.getElementById('loadMoreBtn').addEventListener('click', loadMoreLogs);
            
            // Pagination controls
            document.getElementById('firstPageBtn').addEventListener('click', () => goToPage(1));
            document.getElementById('prevPageBtn').addEventListener('click', () => goToPage(currentPage - 1));
            document.getElementById('nextPageBtn').addEventListener('click', () => goToPage(currentPage + 1));
            document.getElementById('lastPageBtn').addEventListener('click', () => goToPage(totalPages));
            document.getElementById('firstPageBtn2').addEventListener('click', () => goToPage(1));
            document.getElementById('prevPageBtn2').addEventListener('click', () => goToPage(currentPage - 1));
            document.getElementById('nextPageBtn2').addEventListener('click', () => goToPage(currentPage + 1));
            document.getElementById('lastPageBtn2').addEventListener('click', () => goToPage(totalPages));
            document.getElementById('goToPageBtn').addEventListener('click', () => {
                const pageInput = document.getElementById('pageInput');
                const page = parseInt(pageInput.value);
                if (page >= 1 && page <= totalPages) {
                    goToPage(page);
                }
            });
            document.getElementById('perPageSelect').addEventListener('change', (e) => {
                perPage = parseInt(e.target.value);
                currentPage = 1;
                loadPage(currentPage);
            });
        }

        function applyFilters() {
            const logs = document.querySelectorAll('.log-entry');
            let visibleCount = 0;

            logs.forEach(log => {
                const level = log.getAttribute('data-level');
                const message = log.querySelector('.log-message').textContent;
                
                const matchesLevel = currentFilter === 'all' || level === currentFilter;
                const matchesSearch = !searchTerm || message.toLowerCase().includes(searchTerm.toLowerCase());
                
                if (matchesLevel && matchesSearch) {
                    log.style.display = '';
                    visibleCount++;
                } else {
                    log.style.display = 'none';
                }
            });

            // Show empty state if no logs visible
            const container = document.getElementById('logsContainer');
            const emptyState = container.querySelector('.empty-state');
            
            if (visibleCount === 0 && !emptyState && container.querySelectorAll('.log-entry').length > 0) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <div class="empty-icon"><i class="fas fa-search"></i></div>
                    <div>No matching logs found</div>
                `;
                container.appendChild(emptyDiv);
            } else if (visibleCount > 0 && emptyState) {
                emptyState.remove();
            }
        }

        function togglePause() {
            isPaused = !isPaused;
            const btn = document.getElementById('pauseBtn');
            
            if (isPaused) {
                btn.innerHTML = '<i class="fas fa-play"></i>';
                btn.classList.add('active');
                if (eventSource) {
                    eventSource.close();
                }
                showToast('Stream paused', 'info');
            } else {
                btn.innerHTML = '<i class="fas fa-pause"></i>';
                btn.classList.remove('active');
                connectToStream();
                showToast('Stream resumed', 'success');
            }
        }

        function toggleAutoScroll() {
            autoScroll = !autoScroll;
            const btn = document.getElementById('autoScrollBtn');
            
            if (autoScroll) {
                btn.classList.add('active');
                showToast('Auto-scroll enabled', 'success');
            } else {
                btn.classList.remove('active');
                showToast('Auto-scroll disabled', 'info');
            }
        }

        function refreshLogs() {
            console.log('Refreshing logs...');
            
            const btn = document.getElementById('refreshBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i>';
            btn.disabled = true;
            
            // Clear current logs
            const container = document.getElementById('logsContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-sync-alt fa-spin"></i></div>
                    <div>Refreshing logs...</div>
                </div>
            `;
            
            // Reset counts
            updateFilterCounts({});
            
            // Fetch fresh logs from server
            fetch(`${BASE_URL}/api/logs?key=${API_KEY}&limit=50`, {
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Log-Management-Key': API_KEY
                }
            })
            .then(response => response.json())
            .then(data => {
                container.innerHTML = '';
                
                // Handle both data.logs and data.data structures
                const logs = data.logs || data.data || [];
                
                if (logs && logs.length > 0) {
                    logs.reverse().forEach(log => {
                        const entry = createLogEntryElement({
                            level: log.level,
                            message: log.message,
                            channel: log.channel,
                            timestamp: log.created_at,
                            context: log.context ? (typeof log.context === 'string' ? JSON.parse(log.context) : log.context) : null,
                            url: log.url,
                            stack_trace: log.stack_trace
                        });
                        container.appendChild(entry);
                    });
                    updateCounts();
                    applyFilters();
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-stream"></i></div>
                            <div>No logs found</div>
                        </div>
                    `;
                }
                
                // Reconnect to stream
                connectToStream();
                showToast('Logs refreshed', 'success');
            })
            .catch(error => {
                console.error('Failed to refresh logs:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>Failed to load logs</div>
                    </div>
                `;
                showToast('Failed to refresh logs', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }

        function clearLogs() {
            if (confirm('Clear all displayed logs?')) {
                const container = document.getElementById('logsContainer');
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-stream"></i></div>
                        <div>Waiting for logs...</div>
                    </div>
                `;
                
                // Reset counts
                updateFilterCounts({});
                showToast('Display cleared', 'success');
            }
        }

        function exportLogs() {
            const logs = Array.from(document.querySelectorAll('.log-entry:not([style*="display: none"])')).map(entry => {
                return {
                    level: entry.getAttribute('data-level'),
                    timestamp: entry.querySelector('.log-time').textContent,
                    message: entry.querySelector('.log-message').textContent,
                    channel: entry.querySelector('.log-channel').textContent.trim()
                };
            });

            if (logs.length === 0) {
                showToast('No logs to export', 'error');
                return;
            }

            const dataStr = JSON.stringify(logs, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `logs-${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.json`;
            link.click();
            URL.revokeObjectURL(url);
            
            showToast(`Exported ${logs.length} logs`, 'success');
        }

        function testNotification() {
            const btn = document.getElementById('testNotificationBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            btn.disabled = true;

            fetch(`${BASE_URL}/api/log`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Log-Management-Key': API_KEY
                },
                body: JSON.stringify({
                    level: 'info',
                    message: 'Test notification from dashboard - ' + new Date().toLocaleString(),
                    context: { 
                        test: true, 
                        timestamp: new Date().toISOString(),
                        source: 'dashboard'
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Test notification sent!', 'success');
                } else {
                    showToast('Failed to send notification', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error sending notification', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }

        function updateStatistics(data) {
            if (data.total_logs !== undefined) {
                document.getElementById('totalLogs').textContent = data.total_logs.toLocaleString();
            }
            if (data.logs_today !== undefined) {
                document.getElementById('todayLogs').textContent = data.logs_today.toLocaleString();
            }
            if (data.logs_last_hour !== undefined) {
                document.getElementById('hourLogs').textContent = data.logs_last_hour.toLocaleString();
            }
            if (data.error_logs_today !== undefined) {
                document.getElementById('errorLogs').textContent = data.error_logs_today.toLocaleString();
            }
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            container.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
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

        function loadMoreLogs() {
            const btn = document.getElementById('loadMoreBtn');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            btn.disabled = true;
            
            const container = document.getElementById('logsContainer');
            const currentLogCount = container.querySelectorAll('.log-entry').length;
            
            // Build URL with current filter
            let url = `${BASE_URL}/api/logs?key=${API_KEY}&limit=50&offset=${currentLogCount}`;
            if (currentFilter && currentFilter !== 'all') {
                url += `&level=${currentFilter}`;
            }
            
            fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Log-Management-Key': API_KEY
                }
            })
            .then(response => response.json())
            .then(data => {
                // Handle both data.logs and data.data structures
                const logs = data.logs || data.data || [];
                
                if (logs && logs.length > 0) {
                    // Remove empty state if present
                    const emptyState = container.querySelector('.empty-state');
                    if (emptyState) {
                        emptyState.remove();
                    }
                    
                    logs.forEach(log => {
                        const entry = createLogEntryElement({
                            level: log.level,
                            message: log.message,
                            channel: log.channel,
                            timestamp: log.created_at,
                            context: log.context ? (typeof log.context === 'string' ? JSON.parse(log.context) : log.context) : null,
                            url: log.url,
                            stack_trace: log.stack_trace
                        });
                        container.appendChild(entry);
                    });
                    
                    updateCounts();
                    applyFilters();
                    showToast(`Loaded ${logs.length} more logs`, 'success');
                } else {
                    showToast('No more logs to load', 'info');
                }
            })
            .catch(error => {
                console.error('Failed to load more logs:', error);
                showToast('Failed to load more logs', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }

        function goToPage(page) {
            if (page >= 1 && page <= totalPages && page !== currentPage) {
                currentPage = page;
                loadPage(page);
            }
        }

        function loadPage(page) {
            const container = document.getElementById('logsContainer');
            
            // Show loading state
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-spinner fa-spin"></i></div>
                    <div>Loading page ${page}...</div>
                </div>
            `;
            
            // Build URL with pagination and filter
            let url = `${BASE_URL}/api/logs?key=${API_KEY}&page=${page}&per_page=${perPage}`;
            if (currentFilter && currentFilter !== 'all') {
                url += `&level=${currentFilter}`;
            }
            if (searchTerm) {
                url += `&search=${encodeURIComponent(searchTerm)}`;
            }
            
            fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Log-Management-Key': API_KEY
                }
            })
            .then(response => response.json())
            .then(data => {
                container.innerHTML = '';
                
                // Handle pagination info
                if (data.pagination) {
                    currentPage = data.pagination.current_page;
                    totalPages = data.pagination.last_page;
                    totalLogs = data.pagination.total;
                    updatePaginationControls();
                }
                
                // Handle both data.logs and data.data structures
                const logs = data.logs || data.data || [];
                
                if (logs && logs.length > 0) {
                    logs.forEach(log => {
                        const entry = createLogEntryElement({
                            level: log.level,
                            message: log.message,
                            channel: log.channel,
                            timestamp: log.created_at,
                            context: log.context ? (typeof log.context === 'string' ? JSON.parse(log.context) : log.context) : null,
                            url: log.url,
                            stack_trace: log.stack_trace
                        });
                        container.appendChild(entry);
                    });
                    updateCounts();
                    applyFilters();
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-search"></i></div>
                            <div>No logs found for page ${page}</div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Failed to load page:', error);
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div>Failed to load page ${page}</div>
                    </div>
                `;
            });
        }

        function updatePaginationControls() {
            // Update page info
            document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
            document.getElementById('paginationInfo').textContent = `Page ${currentPage} of ${totalPages}`;
            document.getElementById('pageInput').value = currentPage;
            
            // Update total info
            const startItem = ((currentPage - 1) * perPage) + 1;
            const endItem = Math.min(currentPage * perPage, totalLogs);
            document.getElementById('totalInfo').textContent = `Showing ${startItem} to ${endItem} of ${totalLogs} logs`;
            
            // Update button states
            const firstBtns = [document.getElementById('firstPageBtn'), document.getElementById('firstPageBtn2')];
            const prevBtns = [document.getElementById('prevPageBtn'), document.getElementById('prevPageBtn2')];
            const nextBtns = [document.getElementById('nextPageBtn'), document.getElementById('nextPageBtn2')];
            const lastBtns = [document.getElementById('lastPageBtn'), document.getElementById('lastPageBtn2')];
            
            firstBtns.forEach(btn => btn.disabled = currentPage <= 1);
            prevBtns.forEach(btn => btn.disabled = currentPage <= 1);
            nextBtns.forEach(btn => btn.disabled = currentPage >= totalPages);
            lastBtns.forEach(btn => btn.disabled = currentPage >= totalPages);
        }

        function loadFilteredLogs(level) {
            currentFilter = level;
            currentPage = 1;
            loadPage(1);
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>