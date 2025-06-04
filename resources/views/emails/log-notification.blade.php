<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Alert - {{ strtoupper($logData['level']) }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            padding: 20px;
            text-align: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .header.emergency, .header.alert, .header.critical, .header.error {
            background-color: #dc3545;
        }
        .header.warning {
            background-color: #ffc107;
            color: #212529;
        }
        .header.notice, .header.info {
            background-color: #17a2b8;
        }
        .header.debug {
            background-color: #6c757d;
        }
        .content {
            padding: 20px;
        }
        .alert-info {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
        }
        .log-details {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .detail-item {
            margin: 8px 0;
            display: flex;
            flex-wrap: wrap;
        }
        .detail-label {
            font-weight: bold;
            min-width: 100px;
            color: #495057;
        }
        .detail-value {
            flex: 1;
            word-break: break-word;
        }
        .context-data {
            background-color: #f1f3f4;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 15px 5px 15px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .severity-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .severity-emergency, .severity-alert, .severity-critical, .severity-error {
            background-color: #dc3545;
            color: white;
        }
        .severity-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .severity-notice, .severity-info {
            background-color: #17a2b8;
            color: white;
        }
        .severity-debug {
            background-color: #6c757d;
            color: white;
        }
        @media (max-width: 600px) {
            .detail-item {
                flex-direction: column;
            }
            .detail-label {
                min-width: auto;
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ strtolower($logData['level']) }}">
            🚨 Log Alert: {{ strtoupper($logData['level']) }} Level
        </div>
        
        <div class="content">
            <div class="alert-info">
                <strong>Alert Summary:</strong> A {{ strtolower($logData['level']) }} level log has been recorded in your application and requires attention.
            </div>

            <div class="log-details">
                <div class="detail-item">
                    <span class="detail-label">Severity:</span>
                    <span class="detail-value">
                        <span class="severity-badge severity-{{ strtolower($logData['level']) }}">
                            {{ strtoupper($logData['level']) }}
                        </span>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Environment:</span>
                    <span class="detail-value">{{ $logData['environment'] ?? 'Unknown' }}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Timestamp:</span>
                    <span class="detail-value">{{ $logData['timestamp'] ?? now()->toISOString() }}</span>
                </div>

                @if(!empty($logData['channel']))
                <div class="detail-item">
                    <span class="detail-label">Channel:</span>
                    <span class="detail-value">{{ $logData['channel'] }}</span>
                </div>
                @endif

                @if(!empty($logData['url']))
                <div class="detail-item">
                    <span class="detail-label">URL:</span>
                    <span class="detail-value">
                        <a href="{{ $logData['url'] }}" target="_blank">{{ $logData['url'] }}</a>
                    </span>
                </div>
                @endif

                @if(!empty($logData['ip']))
                <div class="detail-item">
                    <span class="detail-label">IP Address:</span>
                    <span class="detail-value">{{ $logData['ip'] }}</span>
                </div>
                @endif

                @if(!empty($logData['user_agent']))
                <div class="detail-item">
                    <span class="detail-label">User Agent:</span>
                    <span class="detail-value">{{ Str::limit($logData['user_agent'], 100) }}</span>
                </div>
                @endif
            </div>

            <div class="detail-item">
                <span class="detail-label">Message:</span>
            </div>
            <div class="log-details">
                {{ $logData['message'] }}
            </div>

            @if(!empty($logData['context']) && is_array($logData['context']) && count($logData['context']) > 0)
            <div class="detail-item">
                <span class="detail-label">Context Data:</span>
            </div>
            <div class="context-data">
                @foreach($logData['context'] as $key => $value)
                    <div><strong>{{ $key }}:</strong> {{ is_array($value) || is_object($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</div>
                @endforeach
            </div>
            @endif

            @if(!empty($logData['extra']) && is_array($logData['extra']) && count($logData['extra']) > 0)
            <div class="detail-item">
                <span class="detail-label">Extra Data:</span>
            </div>
            <div class="context-data">
                @foreach($logData['extra'] as $key => $value)
                    <div><strong>{{ $key }}:</strong> {{ is_array($value) || is_object($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</div>
                @endforeach
            </div>
            @endif

            <div style="text-align: center; margin-top: 30px;">
                @if(config('app.url'))
                <a href="{{ config('app.url') }}" class="button">View Application</a>
                @endif
                
                @if(config('log-management.dashboard.enabled', true))
                <a href="{{ config('app.url') }}/{{ config('log-management.dashboard.route_prefix', 'log-management') }}" class="button">View Log Dashboard</a>
                @endif
            </div>
        </div>

        <div class="footer">
            <p>
                This alert was generated by the Log Management Package for <strong>{{ config('app.name', 'Your Application') }}</strong>.
                <br>
                Timestamp: {{ now()->format('Y-m-d H:i:s T') }}
            </p>
            
            @if(config('log-management.dashboard.enabled', true))
            <p>
                <a href="{{ config('app.url') }}/{{ config('log-management.dashboard.route_prefix', 'log-management') }}/notifications/settings" style="color: #007bff;">Manage Notification Settings</a>
            </p>
            @endif
        </div>
    </div>
</body>
</html>