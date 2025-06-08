<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Log Management Dashboard - {{ config('app.name') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
        
        :root {
            --bg-primary: #0a0a0a;
            --bg-secondary: #111111;
            --bg-tertiary: #1a1a1a;
            --bg-elevated: #222222;
            --border-color: #2a2a2a;
            --border-active: #3a3a3a;
            --text-primary: #ffffff;
            --text-secondary: #a1a1a1;
            --text-tertiary: #6b6b6b;
            --accent-blue: #007aff;
            --accent-green: #32d74b;
            --accent-red: #ff3b30;
            --accent-orange: #ff9f0a;
            --accent-purple: #af52de;
            --accent-pink: #ff2d92;
            --accent-yellow: #ffcc02;
            --glow-blue: rgba(0, 122, 255, 0.4);
            --glow-red: rgba(255, 59, 48, 0.4);
            --glow-green: rgba(50, 215, 75, 0.4);
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.6);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: -1;
            opacity: 0.1;
        }

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-move 20s linear infinite;
        }

        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .bg-orbs {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            animation: float 8s ease-in-out infinite;
        }

        .orb-1 {
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, var(--glow-blue), transparent);
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .orb-2 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--glow-red), transparent);
            top: 60%;
            right: 20%;
            animation-delay: 2s;
        }

        .orb-3 {
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, var(--glow-green), transparent);
            bottom: 10%;
            left: 50%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(20px) rotate(240deg); }
        }

        /* Main Layout */
        .dashboard {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 280px 1fr;
            grid-template-rows: auto 1fr;
            grid-template-areas: 
                "sidebar header"
                "sidebar main";
        }

        /* Header */
        .header {
            grid-area: header;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(20px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .search-container {
            position: relative;
        }

        .search-input {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 10px 16px 10px 40px;
            color: var(--text-primary);
            font-size: 14px;
            width: 300px;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--glow-blue);
            transform: scale(1.02);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-tertiary);
            transition: color 0.3s ease;
        }

        .search-input:focus + .search-icon {
            color: var(--accent-blue);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent-green);
            animation: pulse 2s infinite;
        }

        .status-dot.disconnected {
            background: var(--accent-red);
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Sidebar */
        .sidebar {
            grid-area: sidebar;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            padding: 24px;
            overflow-y: auto;
        }

        .sidebar-section {
            margin-bottom: 32px;
        }

        .sidebar-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .filter-item:hover {
            background: var(--bg-tertiary);
            border-color: var(--border-active);
        }

        .filter-item.active {
            background: var(--bg-elevated);
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 1px var(--glow-blue);
        }

        .filter-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        .filter-count {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .filter-item.active .filter-count {
            background: var(--accent-blue);
            color: white;
        }

        .level-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .level-emergency { background: var(--accent-red); }
        .level-alert { background: var(--accent-orange); }
        .level-critical { background: var(--accent-pink); }
        .level-error { background: var(--accent-red); }
        .level-warning { background: var(--accent-yellow); }
        .level-notice { background: var(--accent-blue); }
        .level-info { background: var(--accent-green); }
        .level-debug { background: var(--text-tertiary); }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-btn:hover {
            background: var(--bg-elevated);
            border-color: var(--accent-blue);
            transform: translateY(-1px);
        }

        /* Main Content */
        .main-content {
            grid-area: main;
            padding: 24px;
            overflow-y: auto;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: left 0.6s;
        }

        .stat-card:hover::before {
            left: 100%;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: var(--bg-tertiary);
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .trend-up { color: var(--accent-green); }
        .trend-down { color: var(--accent-red); }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-subtitle {
            color: var(--text-tertiary);
            font-size: 12px;
            margin-top: 4px;
        }

        /* Logs Container */
        .logs-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }

        .logs-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .logs-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            flex: 1;
        }

        .logs-controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .control-btn {
            padding: 8px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .control-btn:hover {
            background: var(--bg-elevated);
            border-color: var(--accent-blue);
        }

        .control-btn.active {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            color: white;
        }

        /* Log Entries */
        .logs-container {
            max-height: 600px;
            overflow-y: auto;
            position: relative;
        }

        .logs-container::-webkit-scrollbar {
            width: 6px;
        }

        .logs-container::-webkit-scrollbar-track {
            background: var(--bg-tertiary);
        }

        .logs-container::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .logs-container::-webkit-scrollbar-thumb:hover {
            background: var(--border-active);
        }

        .log-entry {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .log-entry:hover {
            background: var(--bg-tertiary);
        }

        .log-entry.new {
            animation: highlight 3s ease-out;
            transform: scale(1.005);
        }

        @keyframes highlight {
            0% {
                background: var(--glow-blue);
                box-shadow: 0 0 20px var(--glow-blue);
            }
            100% {
                background: transparent;
                box-shadow: none;
            }
        }

        .log-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .log-level {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 60px;
            text-align: center;
        }

        .log-level.emergency {
            background: var(--accent-red);
            color: white;
            box-shadow: 0 0 10px var(--glow-red);
        }

        .log-level.alert {
            background: var(--accent-orange);
            color: white;
        }

        .log-level.critical {
            background: var(--accent-pink);
            color: white;
        }

        .log-level.error {
            background: rgba(255, 59, 48, 0.2);
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
            background: rgba(0, 122, 255, 0.2);
            color: var(--accent-blue);
            border: 1px solid var(--accent-blue);
        }

        .log-level.debug {
            background: rgba(107, 107, 107, 0.2);
            color: var(--text-tertiary);
            border: 1px solid var(--text-tertiary);
        }

        .log-timestamp {
            color: var(--text-tertiary);
            font-size: 12px;
            font-family: 'JetBrains Mono', monospace;
        }

        .log-source {
            display: flex;
            align-items: center;
            gap: 4px;
            color: var(--text-secondary);
            font-size: 11px;
        }

        .log-message {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .log-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .log-tag {
            background: var(--bg-elevated);
            color: var(--text-secondary);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }

        .log-details {
            display: none;
            margin-top: 16px;
            padding: 16px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .log-entry.expanded .log-details {
            display: block;
            animation: expand 0.3s ease-out;
        }

        @keyframes expand {
            0% {
                opacity: 0;
                max-height: 0;
            }
            100% {
                opacity: 1;
                max-height: 500px;
            }
        }

        .log-context {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: var(--text-secondary);
            overflow-x: auto;
            margin-top: 12px;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            text-align: center;
        }

        .empty-icon {
            font-size: 64px;
            color: var(--text-tertiary);
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .empty-subtitle {
            color: var(--text-tertiary);
            font-size: 14px;
        }

        /* Loading States */
        .loading-skeleton {
            background: linear-gradient(90deg, var(--bg-tertiary) 25%, var(--bg-elevated) 50%, var(--bg-tertiary) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: var(--radius-md);
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-text {
            height: 16px;
            margin-bottom: 8px;
        }

        .skeleton-text.short {
            width: 60%;
        }

        .skeleton-text.medium {
            width: 80%;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard {
                grid-template-columns: 250px 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
                grid-template-areas: 
                    "header"
                    "main";
            }
            
            .sidebar {
                display: none;
            }
            
            .header {
                padding: 12px 16px;
            }
            
            .search-input {
                width: 200px;
            }
            
            .main-content {
                padding: 16px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }

        /* Real-time Indicator */
        .realtime-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: var(--bg-elevated);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .realtime-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent-green);
            animation: pulse 1s infinite;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .toast {
            background: var(--bg-elevated);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 16px;
            min-width: 300px;
            box-shadow: var(--shadow-lg);
            animation: toast-in 0.3s ease-out;
        }

        .toast.success {
            border-color: var(--accent-green);
            box-shadow: 0 0 20px var(--glow-green);
        }

        .toast.error {
            border-color: var(--accent-red);
            box-shadow: 0 0 20px var(--glow-red);
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
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <div class="bg-grid"></div>
        <div class="bg-orbs">
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>
            <div class="orb orb-3"></div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="dashboard">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search logs..." id="searchInput">
                    <i class="fas fa-search search-icon"></i>
                </div>
                <div class="realtime-indicator">
                    <div class="realtime-dot"></div>
                    Live Stream
                </div>
            </div>
            <div class="header-right">
                <div class="status-indicator">
                    <div class="status-dot" id="connectionStatus"></div>
                    <span id="connectionText">Connecting...</span>
                </div>
                <button class="control-btn" id="settingsBtn">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3 class="sidebar-title">Log Levels</h3>
                <div class="filter-group" id="levelFilters">
                    <div class="filter-item active" data-level="all">
                        <div class="filter-label">
                            <div class="level-indicator" style="background: var(--accent-blue);"></div>
                            All Levels
                        </div>
                        <span class="filter-count" id="count-all">{{ array_sum($levelStats) }}</span>
                    </div>
                    @foreach(['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'] as $level)
                    <div class="filter-item" data-level="{{ $level }}">
                        <div class="filter-label">
                            <div class="level-indicator level-{{ $level }}"></div>
                            {{ ucfirst($level) }}
                        </div>
                        <span class="filter-count" id="count-{{ $level }}">{{ $levelStats[$level] ?? 0 }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="sidebar-section">
                <h3 class="sidebar-title">Quick Actions</h3>
                <div class="quick-actions">
                    <button class="action-btn" id="clearLogsBtn">
                        <i class="fas fa-trash"></i>
                        Clear Logs
                    </button>
                    <button class="action-btn" id="exportLogsBtn">
                        <i class="fas fa-download"></i>
                        Export Logs
                    </button>
                    <button class="action-btn" id="testNotificationBtn">
                        <i class="fas fa-bell"></i>
                        Test Notification
                    </button>
                    <a href="{{ route('log-management.api.stats') }}" class="action-btn">
                        <i class="fas fa-chart-bar"></i>
                        View Statistics
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="color: var(--accent-blue);">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            +12%
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['total_logs']) }}</div>
                    <div class="stat-label">Total Logs</div>
                    <div class="stat-subtitle">All time entries</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="color: var(--accent-green);">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            +5%
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['logs_today']) }}</div>
                    <div class="stat-label">Today's Logs</div>
                    <div class="stat-subtitle">{{ now()->format('M j, Y') }}</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="color: var(--accent-orange);">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            -3%
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['logs_last_hour']) }}</div>
                    <div class="stat-label">Last Hour</div>
                    <div class="stat-subtitle">Recent activity</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon" style="color: var(--accent-red);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-trend {{ $stats['error_logs_today'] > 10 ? 'trend-up' : 'trend-down' }}">
                            <i class="fas fa-{{ $stats['error_logs_today'] > 10 ? 'arrow-up' : 'arrow-down' }}"></i>
                            {{ $stats['error_logs_today'] > 10 ? '+' : '-' }}{{ rand(1, 10) }}%
                        </div>
                    </div>
                    <div class="stat-value">{{ number_format($stats['error_logs_today']) }}</div>
                    <div class="stat-label">Errors Today</div>
                    <div class="stat-subtitle">Critical issues</div>
                </div>
            </div>

            <!-- Logs Section -->
            <div class="logs-section">
                <div class="logs-header">
                    <h2 class="logs-title">
                        <i class="fas fa-stream"></i>
                        Live Log Stream
                    </h2>
                    <div class="logs-controls">
                        <button class="control-btn active" id="autoScrollBtn">
                            <i class="fas fa-arrows-alt-v"></i>
                            Auto Scroll
                        </button>
                        <button class="control-btn" id="pauseBtn">
                            <i class="fas fa-pause"></i>
                            Pause
                        </button>
                        <button class="control-btn" id="refreshBtn">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                    </div>
                </div>

                <div class="logs-container" id="logsContainer">
                    @if($recentLogs->count() > 0)
                        @foreach($recentLogs as $log)
                        <div class="log-entry" data-level="{{ $log->level }}" onclick="toggleLogDetails(this)">
                            <div class="log-header">
                                <span class="log-level {{ $log->level }}">{{ strtoupper($log->level) }}</span>
                                <span class="log-timestamp">{{ $log->created_at->format('H:i:s') }}</span>
                                <div class="log-source">
                                    <i class="fas fa-layer-group"></i>
                                    {{ $log->channel ?: 'app' }}
                                </div>
                            </div>
                            <div class="log-message">{{ $log->message }}</div>
                            <div class="log-meta">
                                @if($log->user_id)
                                    <span class="log-tag">
                                        <i class="fas fa-user"></i>
                                        User: {{ $log->user_id }}
                                    </span>
                                @endif
                                @if($log->ip_address)
                                    <span class="log-tag">
                                        <i class="fas fa-globe"></i>
                                        {{ $log->ip_address }}
                                    </span>
                                @endif
                                @if($log->execution_time)
                                    <span class="log-tag">
                                        <i class="fas fa-stopwatch"></i>
                                        {{ $log->execution_time }}ms
                                    </span>
                                @endif
                                @if($log->memory_usage)
                                    <span class="log-tag">
                                        <i class="fas fa-memory"></i>
                                        {{ round($log->memory_usage / 1024 / 1024, 1) }}MB
                                    </span>
                                @endif
                            </div>
                            <div class="log-details">
                                @if($log->context)
                                    <h4>Context:</h4>
                                    <div class="log-context">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</div>
                                @endif
                                @if($log->stack_trace)
                                    <h4>Stack Trace:</h4>
                                    <div class="log-context">{{ $log->stack_trace }}</div>
                                @endif
                                @if($log->url)
                                    <h4>Request URL:</h4>
                                    <div class="log-context">{{ $log->url }}</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h3 class="empty-title">No logs yet</h3>
                            <p class="empty-subtitle">Logs will appear here when your application generates them</p>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <script>
        // Configuration from Laravel
        const API_KEY = '{{ config("log-management.auth.api_keys.0") ?: env("LOG_MANAGEMENT_API_KEY_1") }}';
        const BASE_URL = '{{ url("/log-management") }}';
        const CSRF_TOKEN = '{{ csrf_token() }}';
        
        // Dashboard state
        let eventSource = null;
        let isConnected = false;
        let isPaused = false;
        let autoScroll = true;
        let currentFilter = 'all';
        let searchTerm = '';
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            connectToStream();
            setupEventListeners();
        });

        function initializeDashboard() {
            console.log('🚀 Initializing Log Management Dashboard');
            updateConnectionStatus('connecting');
            
            // Initialize filter counts from server data
            const levelStats = @json($levelStats);
            updateFilterCounts(levelStats);
        }

        function connectToStream() {
            if (eventSource) {
                eventSource.close();
            }

            const streamUrl = `${BASE_URL}/stream?key=${API_KEY}&include_recent=true`;
            console.log('🔌 Connecting to:', streamUrl);

            eventSource = new EventSource(streamUrl);

            eventSource.onopen = function(event) {
                console.log('✅ SSE Connected');
                updateConnectionStatus('connected');
                showToast('Connected to live stream', 'success');
            };

            eventSource.onmessage = function(event) {
                handleStreamMessage(event);
            };

            eventSource.onerror = function(event) {
                console.error('❌ SSE Error:', event);
                updateConnectionStatus('disconnected');
                
                if (!isPaused) {
                    setTimeout(() => {
                        console.log('🔄 Reconnecting...');
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
                        console.log('📡 Stream connected:', data);
                        updateConnectionStatus('connected');
                        break;

                    case 'log':
                        if (shouldShowLog(data)) {
                            addLogEntry(data);
                            updateFilterCounts();
                        }
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

                    default:
                        console.log('📨 Unknown message:', data);
                }
            } catch (error) {
                console.error('Failed to parse stream data:', error);
            }
        }

        function shouldShowLog(logData) {
            // Apply current filter
            if (currentFilter !== 'all' && logData.level !== currentFilter) {
                return false;
            }

            // Apply search filter
            if (searchTerm && !logData.message.toLowerCase().includes(searchTerm.toLowerCase())) {
                return false;
            }

            return true;
        }

        function addLogEntry(logData) {
            const container = document.getElementById('logsContainer');
            const entry = createLogEntryElement(logData);
            
            // Remove empty state if present
            const emptyState = container.querySelector('.empty-state');
            if (emptyState) {
                emptyState.remove();
            }

            // Add new entry at the top
            container.insertBefore(entry, container.firstChild);

            // Highlight new entry
            entry.classList.add('new');
            setTimeout(() => entry.classList.remove('new'), 3000);

            // Auto scroll if enabled
            if (autoScroll) {
                entry.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            // Limit number of visible logs for performance
            const logs = container.querySelectorAll('.log-entry');
            if (logs.length > 100) {
                logs[logs.length - 1].remove();
            }
        }

        function createLogEntryElement(logData) {
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.setAttribute('data-level', logData.level);
            entry.onclick = () => toggleLogDetails(entry);

            const timestamp = new Date(logData.timestamp).toLocaleTimeString();
            
            entry.innerHTML = `
                <div class="log-header">
                    <span class="log-level ${logData.level}">${logData.level.toUpperCase()}</span>
                    <span class="log-timestamp">${timestamp}</span>
                    <div class="log-source">
                        <i class="fas fa-layer-group"></i>
                        ${logData.channel || 'app'}
                    </div>
                </div>
                <div class="log-message">${escapeHtml(logData.message)}</div>
                <div class="log-meta">
                    ${logData.user_id ? `<span class="log-tag"><i class="fas fa-user"></i> User: ${logData.user_id}</span>` : ''}
                    ${logData.ip_address ? `<span class="log-tag"><i class="fas fa-globe"></i> ${logData.ip_address}</span>` : ''}
                    ${logData.execution_time ? `<span class="log-tag"><i class="fas fa-stopwatch"></i> ${logData.execution_time}ms</span>` : ''}
                    ${logData.memory_usage ? `<span class="log-tag"><i class="fas fa-memory"></i> ${Math.round(logData.memory_usage / 1024 / 1024 * 10) / 10}MB</span>` : ''}
                </div>
                <div class="log-details">
                    ${logData.context && Object.keys(logData.context).length > 0 ? `
                        <h4>Context:</h4>
                        <div class="log-context">${escapeHtml(JSON.stringify(logData.context, null, 2))}</div>
                    ` : ''}
                    ${logData.url ? `
                        <h4>Request URL:</h4>
                        <div class="log-context">${escapeHtml(logData.url)}</div>
                    ` : ''}
                </div>
            `;

            return entry;
        }

        function toggleLogDetails(element) {
            element.classList.toggle('expanded');
        }

        function updateConnectionStatus(status) {
            const statusDot = document.getElementById('connectionStatus');
            const statusText = document.getElementById('connectionText');

            statusDot.className = `status-dot ${status === 'connected' ? '' : 'disconnected'}`;
            statusText.textContent = status === 'connected' ? 'Connected' : 
                                   status === 'connecting' ? 'Connecting...' : 'Disconnected';
            
            isConnected = status === 'connected';
        }

        function updateFilterCounts(levelStats = null) {
            if (!levelStats) {
                // Count current visible logs
                const logs = document.querySelectorAll('.log-entry');
                levelStats = {};
                
                logs.forEach(log => {
                    const level = log.getAttribute('data-level');
                    levelStats[level] = (levelStats[level] || 0) + 1;
                });
            }

            // Update sidebar counts
            let total = 0;
            Object.entries(levelStats).forEach(([level, count]) => {
                const countEl = document.getElementById(`count-${level}`);
                if (countEl) {
                    countEl.textContent = count;
                    total += count;
                }
            });

            // Update total count
            const totalCountEl = document.getElementById('count-all');
            if (totalCountEl) {
                totalCountEl.textContent = total;
            }
        }

        function updateStatistics(data) {
            // Update stat cards with new data
            if (data.total_logs !== undefined) {
                updateStatCard('total_logs', data.total_logs);
            }
            if (data.logs_today !== undefined) {
                updateStatCard('logs_today', data.logs_today);
            }
            if (data.errors_today !== undefined) {
                updateStatCard('errors_today', data.errors_today);
            }
        }

        function updateStatCard(metric, value) {
            // This would update the stat cards dynamically
            console.log(`📊 ${metric}: ${value}`);
        }

        function setupEventListeners() {
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', debounce(function(e) {
                searchTerm = e.target.value;
                filterLogs();
            }, 300));

            // Level filter clicks
            document.getElementById('levelFilters').addEventListener('click', function(e) {
                const filterItem = e.target.closest('.filter-item');
                if (filterItem) {
                    // Update active state
                    document.querySelectorAll('.filter-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    filterItem.classList.add('active');

                    // Apply filter
                    currentFilter = filterItem.getAttribute('data-level');
                    filterLogs();
                }
            });

            // Control buttons
            document.getElementById('pauseBtn').addEventListener('click', togglePause);
            document.getElementById('autoScrollBtn').addEventListener('click', toggleAutoScroll);
            document.getElementById('refreshBtn').addEventListener('click', refreshLogs);

            // Quick action buttons
            document.getElementById('clearLogsBtn').addEventListener('click', clearLogs);
            document.getElementById('exportLogsBtn').addEventListener('click', exportLogs);
            document.getElementById('testNotificationBtn').addEventListener('click', testNotification);
        }

        function filterLogs() {
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
            
            if (visibleCount === 0 && !emptyState) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <div class="empty-icon"><i class="fas fa-search"></i></div>
                    <h3 class="empty-title">No matching logs</h3>
                    <p class="empty-subtitle">Try adjusting your filters or search terms</p>
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
                btn.innerHTML = '<i class="fas fa-play"></i> Resume';
                btn.classList.add('active');
                showToast('Stream paused', 'info');
            } else {
                btn.innerHTML = '<i class="fas fa-pause"></i> Pause';
                btn.classList.remove('active');
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
            console.log('🔄 Refreshing logs...');
            
            // Reconnect to stream
            connectToStream();
            
            // Clear current logs
            const container = document.getElementById('logsContainer');
            container.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-sync-alt fa-spin"></i></div>
                    <h3 class="empty-title">Refreshing logs...</h3>
                    <p class="empty-subtitle">Getting latest log entries</p>
                </div>
            `;
            
            showToast('Refreshing logs...', 'info');
        }

        function clearLogs() {
            if (confirm('Are you sure you want to clear all displayed logs?')) {
                const container = document.getElementById('logsContainer');
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                        <h3 class="empty-title">Logs cleared</h3>
                        <p class="empty-subtitle">New logs will appear here as they are generated</p>
                    </div>
                `;
                showToast('Logs cleared', 'success');
            }
        }

        function exportLogs() {
            const logs = Array.from(document.querySelectorAll('.log-entry')).map(entry => {
                return {
                    level: entry.getAttribute('data-level'),
                    timestamp: entry.querySelector('.log-timestamp').textContent,
                    message: entry.querySelector('.log-message').textContent,
                    channel: entry.querySelector('.log-source').textContent.trim()
                };
            });

            const dataStr = JSON.stringify(logs, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `logs-${new Date().toISOString().slice(0, 10)}.json`;
            link.click();
            URL.revokeObjectURL(url);
            
            showToast('Logs exported successfully', 'success');
        }

        function testNotification() {
            fetch(`${BASE_URL}/api/log`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Log-Management-Key': API_KEY
                },
                body: JSON.stringify({
                    level: 'error',
                    message: 'Test notification from dashboard',
                    context: { test: true, timestamp: new Date().toISOString() }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Test notification sent!', 'success');
                } else {
                    showToast('Failed to send test notification', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error sending test notification', 'error');
            });
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
                toast.remove();
            }, 5000);
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

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>