<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ strtoupper($logData['level']) }} Alert - {{ config('app.name') }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --color-bg: #0A0A0A;
            --color-surface: #111111;
            --color-surface-elevated: #1A1A1A;
            --color-border: #2A2A2A;
            --color-text-primary: #FFFFFF;
            --color-text-secondary: #A1A1A1;
            --color-text-tertiary: #6B6B6B;
            --color-accent: #007AFF;
            --color-success: #32D74B;
            --color-warning: #FF9F0A;
            --color-error: #FF3B30;
            --color-critical: #FF0000;
            --radius-sm: 6px;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
            --shadow-xl: 0 16px 48px rgba(0, 0, 0, 0.6);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-error: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            --gradient-warning: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            --gradient-success: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--color-bg);
            color: var(--color-text-primary);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            padding: 20px;
            margin: 0;
        }

        .email-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            position: relative;
        }

        .email-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }

        /* Header Section */
        .header {
            position: relative;
            padding: 32px;
            background: var(--gradient-error);
            overflow: hidden;
        }

        .header.warning { background: var(--gradient-warning); }
        .header.info { background: var(--gradient-primary); }
        .header.success { background: var(--gradient-success); }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 20px 20px;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-20px, -20px) rotate(360deg); }
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .alert-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            position: relative;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.7; }
        }

        .status-indicator::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: ripple 2s infinite;
        }

        @keyframes ripple {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(2.4); opacity: 0; }
        }

        .alert-badge {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.9);
        }

        .error-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.95);
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .error-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }

        /* Content Sections */
        .content {
            position: relative;
        }

        .section {
            padding: 32px;
            border-bottom: 1px solid var(--color-border);
            position: relative;
            transition: background-color 0.3s ease;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section:hover {
            background: rgba(255, 255, 255, 0.01);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 20px;
        }

        .section-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--color-surface-elevated);
            border: 1px solid var(--color-border);
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .section:hover .section-icon {
            background: var(--color-accent);
            border-color: var(--color-accent);
            transform: scale(1.1);
        }

        /* Error Message */
        .error-message {
            background: var(--color-surface-elevated);
            border: 1px solid rgba(255, 59, 48, 0.2);
            border-left: 3px solid var(--color-error);
            border-radius: var(--radius-md);
            padding: 20px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #ff6b6b;
            position: relative;
            overflow: hidden;
        }

        .error-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 49%, rgba(255, 59, 48, 0.05) 50%, transparent 51%);
            background-size: 20px 20px;
            pointer-events: none;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .info-card {
            background: var(--color-surface-elevated);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            transition: left 0.6s;
        }

        .info-card:hover {
            border-color: var(--color-accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .info-card:hover::before {
            left: 100%;
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--color-text-tertiary);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--color-text-primary);
            word-break: break-word;
        }

        .info-value.code {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Performance Metrics */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .metric-card {
            background: var(--color-surface-elevated);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 20px;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            border-color: var(--color-accent);
            box-shadow: 0 0 20px rgba(0, 122, 255, 0.2);
        }

        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--color-text-primary);
            display: block;
            margin-bottom: 4px;
        }

        .metric-label {
            font-size: 11px;
            color: var(--color-text-tertiary);
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .metric-trend {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--color-success);
        }

        .metric-trend.critical {
            background: var(--color-error);
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }

        /* Stack Trace */
        .stack-trace {
            background: #0D1117;
            border: 1px solid #21262D;
            border-radius: var(--radius-md);
            padding: 20px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 12px;
            line-height: 1.6;
            overflow-x: auto;
            margin-top: 16px;
            max-height: 400px;
            overflow-y: auto;
            position: relative;
        }

        .stack-trace::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .stack-trace::-webkit-scrollbar-thumb {
            background: var(--color-border);
            border-radius: 3px;
        }

        .stack-frame {
            padding: 8px 12px;
            margin: 4px 0;
            border-radius: var(--radius-sm);
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .stack-frame:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .stack-frame-number {
            color: #79C0FF;
            font-weight: 600;
            margin-right: 12px;
        }

        .stack-frame-file {
            color: #7EE787;
        }

        .stack-frame-line {
            color: #FFA657;
        }

        .stack-frame-function {
            color: #D2A8FF;
        }

        /* Context Data */
        .context-container {
            background: var(--color-surface-elevated);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-top: 16px;
        }

        .context-header {
            background: rgba(255, 255, 255, 0.02);
            border-bottom: 1px solid var(--color-border);
            padding: 12px 16px;
            font-weight: 500;
            font-size: 14px;
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .context-content {
            padding: 16px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 12px;
            line-height: 1.6;
            color: var(--color-text-secondary);
            max-height: 300px;
            overflow-y: auto;
        }

        /* Suggestions */
        .suggestions-container {
            background: linear-gradient(135deg, rgba(0, 122, 255, 0.1) 0%, rgba(0, 122, 255, 0.05) 100%);
            border: 1px solid rgba(0, 122, 255, 0.2);
            border-radius: var(--radius-md);
            padding: 24px;
            margin-top: 20px;
        }

        .suggestions-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--color-accent);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .suggestion-item {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .suggestion-item:last-child {
            margin-bottom: 0;
        }

        .suggestion-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateX(4px);
        }

        .suggestion-title {
            font-weight: 600;
            color: var(--color-text-primary);
            margin-bottom: 4px;
            font-size: 14px;
        }

        .suggestion-desc {
            color: var(--color-text-secondary);
            font-size: 13px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transition: all 0.5s ease;
            transform: translate(-50%, -50%);
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: var(--color-surface-elevated);
            color: var(--color-text-primary);
            border: 1px solid var(--color-border);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--color-accent);
        }

        /* Timeline */
        .timeline {
            position: relative;
            padding-left: 32px;
            margin-top: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--color-accent), transparent);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 24px;
            padding-left: 24px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -28px;
            top: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-accent);
            border: 2px solid var(--color-surface);
            box-shadow: 0 0 0 2px var(--color-accent);
            animation: pulse-timeline 2s infinite;
        }

        @keyframes pulse-timeline {
            0%, 100% { box-shadow: 0 0 0 2px var(--color-accent); }
            50% { box-shadow: 0 0 0 6px rgba(0, 122, 255, 0.3); }
        }

        .timeline-time {
            font-size: 11px;
            color: var(--color-text-tertiary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .timeline-content {
            margin-top: 4px;
            font-size: 14px;
            color: var(--color-text-secondary);
        }

        /* Footer */
        .footer {
            background: var(--color-surface-elevated);
            border-top: 1px solid var(--color-border);
            padding: 24px 32px;
            text-align: center;
        }

        .footer-content {
            color: var(--color-text-tertiary);
            font-size: 13px;
            margin-bottom: 16px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
        }

        .footer-link {
            color: var(--color-accent);
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.3s ease;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
        }

        .footer-link:hover {
            background: rgba(0, 122, 255, 0.1);
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .email-container {
                border-radius: var(--radius-lg);
            }

            .header {
                padding: 24px;
            }

            .error-title {
                font-size: 24px;
            }

            .section {
                padding: 24px;
            }

            .info-grid,
            .metrics-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .footer-links {
                flex-direction: column;
                gap: 12px;
            }
        }

        /* Loading Animation */
        .loading-shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            background-size: 200% 100%;
            animation: shimmer-loading 1.5s infinite;
        }

        @keyframes shimmer-loading {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header {{ strtolower($logData['level']) }}">
            <div class="header-content">
                <div class="alert-meta">
                    <div class="status-indicator"></div>
                    <div class="alert-badge">{{ $logData['datetime'] ?? now()->format('M j, Y \a\t g:i A') }}</div>
                    @if(!empty($logData['request_id']))
                        <div class="alert-badge">{{ substr($logData['request_id'], 0, 8) }}</div>
                    @endif
                    @if(!empty($logData['user_id']))
                        <div class="alert-badge">User {{ $logData['user_id'] }}</div>
                    @endif
                </div>
                
                <h1 class="error-title">{{ strtoupper($logData['level']) }} Alert</h1>
                <p class="error-subtitle">{{ config('app.name') }} • {{ $logData['environment'] ?? 'Production' }} Environment</p>
            </div>
        </div>

        <div class="content">
            <!-- Error Message Section -->
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">💬</div>
                    Error Details
                </h2>
                
                <div class="error-message">{{ $logData['message'] }}</div>
                
                <!-- Quick Info Grid -->
                <div class="info-grid">
                    @if(!empty($logData['file_path']))
                    <div class="info-card">
                        <div class="info-label">File</div>
                        <div class="info-value code">{{ basename($logData['file_path']) }}</div>
                    </div>
                    @endif
                    
                    @if(!empty($logData['line_number']))
                    <div class="info-card">
                        <div class="info-label">Line</div>
                        <div class="info-value">{{ $logData['line_number'] }}</div>
                    </div>
                    @endif
                    
                    @if(!empty($logData['url']))
                    <div class="info-card">
                        <div class="info-label">Route</div>
                        <div class="info-value code">{{ parse_url($logData['url'], PHP_URL_PATH) }}</div>
                    </div>
                    @endif
                    
                    @if(!empty($logData['method']))
                    <div class="info-card">
                        <div class="info-label">Method</div>
                        <div class="info-value">{{ strtoupper($logData['method']) }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Performance Metrics -->
            @if(!empty($logData['execution_time']) || !empty($logData['memory_usage']))
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">⚡</div>
                    Performance Impact
                </h2>
                
                <div class="metrics-grid">
                    @if(!empty($logData['execution_time']))
                    <div class="metric-card">
                        <div class="metric-trend {{ $logData['execution_time'] > 1000 ? 'critical' : '' }}"></div>
                        <span class="metric-value">{{ $logData['execution_time'] }}ms</span>
                        <span class="metric-label">Response Time</span>
                    </div>
                    @endif
                    
                    @if(!empty($logData['memory_usage']))
                    <div class="metric-card">
                        <div class="metric-trend {{ $logData['memory_usage'] > 128*1024*1024 ? 'critical' : '' }}"></div>
                        <span class="metric-value">{{ round($logData['memory_usage'] / 1024 / 1024, 1) }}MB</span>
                        <span class="metric-label">Memory Peak</span>
                    </div>
                    @endif
                    
                    <div class="metric-card">
                        <span class="metric-value">{{ $logData['environment'] ?? 'PROD' }}</span>
                        <span class="metric-label">Environment</span>
                    </div>
                    
                    <div class="metric-card">
                        <span class="metric-value">{{ $logData['channel'] ?? 'APP' }}</span>
                        <span class="metric-label">Channel</span>
                    </div>
                </div>
            </div>
            @endif

            <!-- Request Context -->
            @if(!empty($logData['url']) || !empty($logData['ip_address']))
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">🌐</div>
                    Request Context
                </h2>
                
                <div class="info-grid">
                    @if(!empty($logData['url']))
                    <div class="info-card">
                        <div class="info-label">Full URL</div>
                        <div class="info-value code">{{ $logData['url'] }}</div>
                    </div>
                    @endif
                    
                    @if(!empty($logData['ip_address']))
                    <div class="info-card">
                        <div class="info-label">Client IP</div>
                        <div class="info-value">{{ $logData['ip_address'] }}</div>
                    </div>
                    @endif
                    
                    @if(!empty($logData['user_agent']))
                    <div class="info-card">
                        <div class="info-label">User Agent</div>
                        <div class="info-value">{{ Str::limit($logData['user_agent'], 60) }}</div>
                    </div>
                    @endif
                    
                    @if(!empty($logData['session_id']))
                    <div class="info-card">
                        <div class="info-label">Session</div>
                        <div class="info-value code">{{ substr($logData['session_id'], 0, 16) }}...</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Context Data -->
            @if(!empty($logData['context']) && is_array($logData['context']) && count($logData['context']) > 0)
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">🔍</div>
                    Debug Context
                </h2>
                
                <div class="context-container">
                    <div class="context-header">
                        <span>📋</span>
                        Application State
                    </div>
                    <div class="context-content">
                        <pre>{{ json_encode($logData['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stack Trace -->
            @if(!empty($logData['stack_trace']))
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">📋</div>
                    Stack Trace
                </h2>
                
                <div class="stack-trace">
                    @php
                        $lines = explode("\n", $logData['stack_trace']);
                        $frameNumber = 0;
                    @endphp
                    @foreach($lines as $line)
                        @if(trim($line))
                            <div class="stack-frame">
                                @if(preg_match('/^#(\d+)\s+(.+?)\((\d+)\):\s*(.+)$/', trim($line), $matches))
                                    <span class="stack-frame-number">#{{ $matches[1] }}</span>
                                    <span class="stack-frame-file">{{ basename($matches[2]) }}</span>:<span class="stack-frame-line">{{ $matches[3] }}</span>
                                    <br>&nbsp;&nbsp;&nbsp;&nbsp;<span class="stack-frame-function">{{ $matches[4] }}</span>
                                @else
                                    {{ $line }}
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            <!-- AI-Powered Suggestions -->
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">🤖</div>
                    Intelligent Suggestions
                </h2>
                
                <div class="suggestions-container">
                    <div class="suggestions-title">
                        💡 Recommended Actions
                    </div>
                    
                    @php
                        $suggestions = [];
                        $errorMessage = strtolower($logData['message']);
                        
                        // Enhanced AI-like error pattern matching
                        if (str_contains($errorMessage, 'connection') && (str_contains($errorMessage, 'refused') || str_contains($errorMessage, 'timeout'))) {
                            $suggestions[] = [
                                'title' => 'Database Connection Issue',
                                'desc' => 'Verify database credentials in .env, check if DB server is running, and test network connectivity.',
                                'priority' => 'high'
                            ];
                        } elseif (str_contains($errorMessage, 'permission denied') || str_contains($errorMessage, 'forbidden')) {
                            $suggestions[] = [
                                'title' => 'File/Directory Permissions',
                                'desc' => 'Run: sudo chown -R www-data:www-data storage bootstrap/cache && sudo chmod -R 755 storage',
                                'priority' => 'high'
                            ];
                        } elseif (str_contains($errorMessage, 'class') && str_contains($errorMessage, 'not found')) {
                            $suggestions[] = [
                                'title' => 'Autoloader Issue',
                                'desc' => 'Execute: composer dump-autoload -o to regenerate class mappings.',
                                'priority' => 'medium'
                            ];
                        } elseif (str_contains($errorMessage, 'undefined method') || str_contains($errorMessage, 'call to undefined')) {
                            $suggestions[] = [
                                'title' => 'Method/Function Missing',
                                'desc' => 'Check for typos in method names or verify the class implements the required interface.',
                                'priority' => 'medium'
                            ];
                        } elseif (str_contains($errorMessage, 'syntax error') || str_contains($errorMessage, 'parse error')) {
                            $suggestions[] = [
                                'title' => 'PHP Syntax Error',
                                'desc' => 'Review the file for missing semicolons, brackets, quotes, or incorrect PHP syntax.',
                                'priority' => 'high'
                            ];
                        } elseif (str_contains($errorMessage, 'memory') && str_contains($errorMessage, 'exhausted')) {
                            $suggestions[] = [
                                'title' => 'Memory Limit Exceeded',
                                'desc' => 'Increase memory_limit in php.ini or optimize code to reduce memory usage.',
                                'priority' => 'high'
                            ];
                        } elseif (str_contains($errorMessage, 'csrf') || str_contains($errorMessage, 'token mismatch')) {
                            $suggestions[] = [
                                'title' => 'CSRF Token Issue',
                                'desc' => 'Ensure @csrf directive is present in forms or verify token expiration settings.',
                                'priority' => 'medium'
                            ];
                        } elseif (str_contains($errorMessage, 'queue') || str_contains($errorMessage, 'job')) {
                            $suggestions[] = [
                                'title' => 'Queue Processing Error',
                                'desc' => 'Check queue worker status and restart with: php artisan queue:restart',
                                'priority' => 'medium'
                            ];
                        } elseif (str_contains($errorMessage, 'route') || str_contains($errorMessage, 'not found')) {
                            $suggestions[] = [
                                'title' => 'Routing Issue',
                                'desc' => 'Clear route cache with: php artisan route:clear and verify route definitions.',
                                'priority' => 'medium'
                            ];
                        }
                        
                        // Environment-specific suggestions
                        if (($logData['environment'] ?? '') === 'production') {
                            $suggestions[] = [
                                'title' => 'Production Optimization',
                                'desc' => 'Ensure debug mode is off, caches are optimized, and logging is configured properly.',
                                'priority' => 'low'
                            ];
                        }
                        
                        // Performance-based suggestions
                        if (!empty($logData['execution_time']) && $logData['execution_time'] > 2000) {
                            $suggestions[] = [
                                'title' => 'Performance Issue Detected',
                                'desc' => 'Response time exceeds 2s. Consider query optimization, caching, or code profiling.',
                                'priority' => 'medium'
                            ];
                        }
                        
                        if (!empty($logData['memory_usage']) && $logData['memory_usage'] > 128*1024*1024) {
                            $suggestions[] = [
                                'title' => 'High Memory Usage',
                                'desc' => 'Memory usage >128MB detected. Review memory-intensive operations and optimize.',
                                'priority' => 'medium'
                            ];
                        }
                        
                        // Default suggestion if no specific pattern matched
                        if (empty($suggestions)) {
                            $suggestions[] = [
                                'title' => 'General Debugging Steps',
                                'desc' => 'Check Laravel logs, verify environment configuration, and review recent code changes.',
                                'priority' => 'low'
                            ];
                        }
                        
                        // Sort by priority
                        usort($suggestions, function($a, $b) {
                            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
                            return ($priorities[$b['priority']] ?? 1) - ($priorities[$a['priority']] ?? 1);
                        });
                    @endphp
                    
                    @foreach($suggestions as $index => $suggestion)
                    <div class="suggestion-item" style="animation-delay: {{ $index * 0.1 }}s;">
                        <div class="suggestion-title">
                            @if($suggestion['priority'] === 'high')
                                🔴
                            @elseif($suggestion['priority'] === 'medium')
                                🟡
                            @else
                                🟢
                            @endif
                            {{ $suggestion['title'] }}
                        </div>
                        <div class="suggestion-desc">{{ $suggestion['desc'] }}</div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    @if(config('app.url'))
                    <a href="{{ config('app.url') }}/log-management" class="btn btn-primary">
                        <span>📊</span>
                        View Dashboard
                    </a>
                    @endif
                    
                    @if(!empty($logData['url']))
                    <a href="{{ $logData['url'] }}" class="btn btn-secondary">
                        <span>🔗</span>
                        Reproduce Error
                    </a>
                    @endif
                    
                    <a href="{{ config('app.url') }}/log-management/api/logs/{{ $logData['id'] ?? '' }}" class="btn btn-secondary">
                        <span>🔍</span>
                        View Details
                    </a>
                    
                    <a href="mailto:dev-team@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com' }}?subject={{ urlencode('Error Alert: ' . $logData['level']) }}&body={{ urlencode('Error: ' . $logData['message']) }}" class="btn btn-secondary">
                        <span>📧</span>
                        Contact Team
                    </a>
                </div>
            </div>

            <!-- Event Timeline -->
            @if(!empty($logData['context']['timeline']) || !empty($logData['context']['user_actions']))
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">⏰</div>
                    Event Timeline
                </h2>
                
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-time">{{ date('H:i:s', strtotime($logData['datetime'] ?? 'now')) }}</div>
                        <div class="timeline-content">
                            <strong>{{ strtoupper($logData['level']) }} Event Occurred</strong><br>
                            {{ $logData['message'] }}
                        </div>
                    </div>
                    
                    @if(!empty($logData['context']['user_actions']))
                        @foreach(array_slice($logData['context']['user_actions'], 0, 3) as $action)
                        <div class="timeline-item">
                            <div class="timeline-time">{{ $action['time'] ?? 'Earlier' }}</div>
                            <div class="timeline-content">{{ $action['description'] ?? 'User action performed' }}</div>
                        </div>
                        @endforeach
                    @else
                        <div class="timeline-item">
                            <div class="timeline-time">{{ date('H:i:s', strtotime('-30 seconds', strtotime($logData['datetime'] ?? 'now'))) }}</div>
                            <div class="timeline-content">Request initiated from {{ $logData['ip_address'] ?? 'unknown IP' }}</div>
                        </div>
                        
                        @if(!empty($logData['execution_time']))
                        <div class="timeline-item">
                            <div class="timeline-time">{{ date('H:i:s', strtotime('-' . ($logData['execution_time']/1000) . ' seconds', strtotime($logData['datetime'] ?? 'now'))) }}</div>
                            <div class="timeline-content">Processing started ({{ $logData['execution_time'] }}ms duration)</div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
            @endif

            <!-- System Information -->
            <div class="section">
                <h2 class="section-title">
                    <div class="section-icon">⚙️</div>
                    System Information
                </h2>
                
                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-label">Laravel Version</div>
                        <div class="info-value">{{ app()->version() }}</div>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-label">PHP Version</div>
                        <div class="info-value">{{ phpversion() }}</div>
                    </div>
                    
                    @if(!empty($logData['extra']['server_name']))
                    <div class="info-card">
                        <div class="info-label">Server</div>
                        <div class="info-value code">{{ $logData['extra']['server_name'] }}</div>
                    </div>
                    @endif
                    
                    <div class="info-card">
                        <div class="info-label">Timestamp</div>
                        <div class="info-value">{{ $logData['datetime'] ?? now()->toISOString() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-content">
                <strong>{{ config('app.name') }}</strong> Error Monitoring System<br>
                This alert was automatically generated when a {{ strtolower($logData['level']) }} level event was detected.
            </div>
            <div class="footer-links">
                @if(config('app.url'))
                <a href="{{ config('app.url') }}/log-management" class="footer-link">📊 Dashboard</a>
                @endif
                <a href="{{ config('app.url') }}/log-management/docs" class="footer-link">📚 Documentation</a>
                <a href="{{ config('app.url') }}/log-management/settings" class="footer-link">⚙️ Notification Settings</a>
                <a href="https://github.com/{{ config('app.name') }}/issues" class="footer-link">🐛 Report Bug</a>
                <a href="https://support.{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com' }}" class="footer-link">🆘 Get Support</a>
            </div>
        </div>
    </div>

    <script>
        // Add micro-interactions for email clients that support JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Animate sections on load
            const sections = document.querySelectorAll('.section');
            sections.forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add copy-to-clipboard functionality for code blocks
            const codeElements = document.querySelectorAll('.info-value.code, .error-message');
            codeElements.forEach(element => {
                element.style.cursor = 'pointer';
                element.title = 'Click to copy';
                element.addEventListener('click', function() {
                    navigator.clipboard.writeText(this.textContent).then(() => {
                        const originalText = this.textContent;
                        this.textContent = '✓ Copied!';
                        this.style.color = '#32D74B';
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.style.color = '';
                        }, 2000);
                    });
                });
            });

            // Enhance suggestion items with hover effects
            const suggestions = document.querySelectorAll('.suggestion-item');
            suggestions.forEach(suggestion => {
                suggestion.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(8px) scale(1.02)';
                });
                suggestion.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(4px) scale(1)';
                });
            });

            // Add loading state simulation for buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.classList.contains('loading')) return;
                    
                    const originalContent = this.innerHTML;
                    this.classList.add('loading');
                    this.innerHTML = '<span style="animation: spin 1s linear infinite;">⚪</span> Loading...';
                    this.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.classList.remove('loading');
                        this.style.pointerEvents = '';
                    }, 1500);
                });
            });
        });

        // CSS animations for the script
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            .suggestion-item {
                animation: slideInLeft 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
                opacity: 0;
                transform: translateX(-20px);
            }
            
            @keyframes slideInLeft {
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>