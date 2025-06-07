<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ strtoupper($logData['level'] ?? 'ERROR') }} Alert - {{ config('app.name') }}</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f8fafc; line-height: 1.6;">
    
    <!-- Email Wrapper -->
    <div style="width: 100%; background-color: #f8fafc; padding: 20px 0;">
        
        <!-- Email Container -->
        <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            
            <!-- Header -->
            @php
                $headerBg = '#dc2626'; // error default
                if (isset($logData['level'])) {
                    switch (strtolower($logData['level'])) {
                        case 'warning':
                            $headerBg = '#d97706';
                            break;
                        case 'info':
                            $headerBg = '#2563eb';
                            break;
                        case 'success':
                            $headerBg = '#059669';
                            break;
                    }
                }
            @endphp
            <div style="background-color: {{ $headerBg }}; color: #ffffff; padding: 32px 24px; text-align: left;">
                
                <!-- Alert Meta -->
                <div style="margin-bottom: 16px;">
                    <span style="display: inline-block; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 8px; margin-bottom: 4px;">
                        {{ $logData['datetime'] ?? now()->format('M j, Y \a\t g:i A') }}
                    </span>
                    
                    @if(!empty($logData['request_id']))
                    <span style="display: inline-block; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 8px; margin-bottom: 4px;">
                        🆔 {{ substr($logData['request_id'], 0, 8) }}
                    </span>
                    @endif
                    
                    @if(!empty($logData['user_id']))
                    <span style="display: inline-block; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-right: 8px; margin-bottom: 4px;">
                        👤 User {{ $logData['user_id'] }}
                    </span>
                    @endif
                </div>
                
                <!-- Alert Title & Subtitle -->
                <h1 style="font-size: 28px; font-weight: 700; margin: 0 0 8px 0; color: #ffffff;">
                    {{ strtoupper($logData['level'] ?? 'ERROR') }} Alert
                </h1>
                <p style="font-size: 16px; margin: 0; color: rgba(255, 255, 255, 0.9);">
                    {{ config('app.name') }} • {{ $logData['environment'] ?? 'Production' }} Environment
                </p>
            </div>

            <!-- Error Details Section -->
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        🚨
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Error Details</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Primary error information and context</p>
                    </div>
                </div>
                
                <!-- Error Message -->
                <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #dc2626; border-radius: 8px; padding: 16px; font-family: 'Courier New', Monaco, monospace; font-size: 14px; color: #991b1b; word-break: break-word; margin-bottom: 20px;">
                    {{ $logData['message'] ?? 'An error occurred' }}
                </div>
                
                <!-- Info Cards -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 8px;">
                    <tr>
                        @if(!empty($logData['file_path']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">File</div>
                            <div style="font-family: 'Courier New', Monaco, monospace; background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb; font-weight: 500; font-size: 14px; color: #111827;">
                                {{ basename($logData['file_path']) }}
                            </div>
                        </td>
                        @endif
                        
                        @if(!empty($logData['line_number']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Line</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $logData['line_number'] }}</div>
                        </td>
                        @endif
                        
                        @if(!empty($logData['url']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Route</div>
                            <div style="font-family: 'Courier New', Monaco, monospace; background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb; font-weight: 500; font-size: 14px; color: #111827;">
                                {{ parse_url($logData['url'], PHP_URL_PATH) }}
                            </div>
                        </td>
                        @endif
                        
                        @if(!empty($logData['method']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Method</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ strtoupper($logData['method']) }}</div>
                        </td>
                        @endif
                    </tr>
                </table>
            </div>

            <!-- Performance Impact -->
            @if(!empty($logData['execution_time']) || !empty($logData['memory_usage']))
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        ⚡
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Performance Impact</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Request timing and resource usage</p>
                    </div>
                </div>
                
                <!-- Metric Cards -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 8px;">
                    <tr>
                        @if(!empty($logData['execution_time']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top; width: 25%; position: relative;">
                            @php $isSlowResponse = $logData['execution_time'] > 1000; @endphp
                            @if($isSlowResponse)
                            <div style="position: absolute; top: 12px; right: 12px; width: 8px; height: 8px; border-radius: 50%; background-color: #ef4444;"></div>
                            @endif
                            <span style="font-size: 24px; font-weight: 800; color: #111827; display: block; margin-bottom: 4px;">{{ $logData['execution_time'] }}ms</span>
                            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 500;">Response Time</div>
                        </td>
                        @endif
                        
                        @if(!empty($logData['memory_usage']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top; width: 25%; position: relative;">
                            @php $isHighMemory = $logData['memory_usage'] > 128*1024*1024; @endphp
                            @if($isHighMemory)
                            <div style="position: absolute; top: 12px; right: 12px; width: 8px; height: 8px; border-radius: 50%; background-color: #ef4444;"></div>
                            @endif
                            <span style="font-size: 24px; font-weight: 800; color: #111827; display: block; margin-bottom: 4px;">{{ round($logData['memory_usage'] / 1024 / 1024, 1) }}MB</span>
                            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 500;">Memory Peak</div>
                        </td>
                        @endif
                        
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top; width: 25%;">
                            <span style="font-size: 24px; font-weight: 800; color: #111827; display: block; margin-bottom: 4px;">{{ $logData['environment'] ?? 'PROD' }}</span>
                            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 500;">Environment</div>
                        </td>
                        
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; vertical-align: top; width: 25%;">
                            <span style="font-size: 24px; font-weight: 800; color: #111827; display: block; margin-bottom: 4px;">{{ $logData['channel'] ?? 'APP' }}</span>
                            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 500;">Channel</div>
                        </td>
                    </tr>
                </table>
            </div>
            @endif

            <!-- Request Context -->
            @if(!empty($logData['url']) || !empty($logData['ip_address']))
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        🌐
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Request Context</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">HTTP request details and client information</p>
                    </div>
                </div>
                
                <!-- Request Info Cards -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 8px;">
                    <tr>
                        @if(!empty($logData['url']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 50%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Full URL</div>
                            <div style="font-family: 'Courier New', Monaco, monospace; background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb; font-weight: 500; font-size: 14px; color: #111827; word-break: break-all;">
                                {{ $logData['url'] }}
                            </div>
                        </td>
                        @endif
                        
                        @if(!empty($logData['ip_address']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Client IP</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $logData['ip_address'] }}</div>
                        </td>
                        @endif
                    </tr>
                    @if(!empty($logData['user_agent']) || !empty($logData['session_id']))
                    <tr>
                        @if(!empty($logData['user_agent']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 50%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">User Agent</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827; word-break: break-word;">{{ Str::limit($logData['user_agent'], 50) }}</div>
                        </td>
                        @endif
                        
                        @if(!empty($logData['session_id']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Session</div>
                            <div style="font-family: 'Courier New', Monaco, monospace; background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb; font-weight: 500; font-size: 14px; color: #111827;">
                                {{ substr($logData['session_id'], 0, 16) }}...
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endif
                </table>
            </div>
            @endif

            <!-- Debug Context -->
            @if(!empty($logData['context']) && is_array($logData['context']) && count($logData['context']) > 0)
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        🔍
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Debug Context</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Application state and variables</p>
                    </div>
                </div>
                
                <!-- Context Container -->
                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-top: 16px;">
                    <div style="background-color: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 12px 16px; font-weight: 600; font-size: 14px; color: #6b7280;">
                        📋 Application State
                    </div>
                    <div style="padding: 16px; font-family: 'Courier New', Monaco, monospace; font-size: 12px; color: #6b7280; max-height: 250px; overflow-y: auto;">
                        <pre style="margin: 0; white-space: pre-wrap; word-break: break-word;">{{ json_encode($logData['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stack Trace -->
            @if(!empty($logData['stack_trace']))
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        📋
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Stack Trace</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Execution path and call stack</p>
                    </div>
                </div>
                
                <!-- Stack Trace Content -->
                <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; font-family: 'Courier New', Monaco, monospace; font-size: 12px; overflow-x: auto; max-height: 300px; overflow-y: auto;">
                    @php
                        $lines = explode("\n", $logData['stack_trace']);
                    @endphp
                    @foreach($lines as $line)
                        @if(trim($line))
                            <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                                @if(preg_match('/^#(\d+)\s+(.+?)\((\d+)\):\s*(.+)$/', trim($line), $matches))
                                    <span style="color: #2563eb; font-weight: 600; margin-right: 8px;">#{{ $matches[1] }}</span>
                                    <span style="color: #059669; font-weight: 600;">{{ basename($matches[2]) }}</span>:<span style="color: #d97706; font-weight: 600;">{{ $matches[3] }}</span>
                                    <br>&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #6b7280;">{{ $matches[4] }}</span>
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
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        🤖
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Intelligent Suggestions</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">AI-powered recommendations for quick resolution</p>
                    </div>
                </div>
                
                <!-- Suggestions Container -->
                <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 24px; margin-top: 20px;">
                    <div style="font-size: 18px; font-weight: 700; color: #1e40af; margin-bottom: 16px;">
                        💡 Recommended Actions
                    </div>
                    
                    @php
                        $suggestions = [];
                        $errorMessage = strtolower($logData['message'] ?? '');
                        
                        if (str_contains($errorMessage, 'class') && str_contains($errorMessage, 'not found')) {
                            $suggestions[] = [
                                'title' => 'Class Autoloader Issue',
                                'desc' => 'Execute: composer dump-autoload -o to regenerate class mappings.',
                                'priority' => 'high'
                            ];
                        }
                        
                        if (str_contains($errorMessage, 'view') && str_contains($errorMessage, 'not found')) {
                            $suggestions[] = [
                                'title' => 'Missing View File',
                                'desc' => 'Check if the view file exists in resources/views/ directory.',
                                'priority' => 'high'
                            ];
                        }
                        
                        if (str_contains($errorMessage, 'optimize') || str_contains($errorMessage, 'command')) {
                            $suggestions[] = [
                                'title' => 'Artisan Command Issue',
                                'desc' => 'Run php artisan list to see available commands or check command syntax.',
                                'priority' => 'medium'
                            ];
                        }
                        
                        if (!empty($logData['execution_time']) && $logData['execution_time'] > 1000) {
                            $suggestions[] = [
                                'title' => 'Performance Issue',
                                'desc' => 'Response time exceeds 1s. Consider query optimization or caching.',
                                'priority' => 'medium'
                            ];
                        }
                        
                        if (empty($suggestions)) {
                            $suggestions[] = [
                                'title' => 'General Debugging',
                                'desc' => 'Check Laravel logs and verify environment configuration.',
                                'priority' => 'low'
                            ];
                        }
                        
                        // Priority colors
                        $priorityColors = [
                            'high' => '#ef4444',
                            'medium' => '#f59e0b',
                            'low' => '#10b981'
                        ];
                    @endphp
                    
                    @foreach($suggestions as $suggestion)
                    <div style="background-color: #ffffff; border: 1px solid #e5e7eb; border-left: 4px solid {{ $priorityColors[$suggestion['priority']] }}; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                        <div style="font-weight: 700; color: #111827; margin-bottom: 8px; font-size: 14px;">{{ $suggestion['title'] }}</div>
                        <div style="color: #6b7280; font-size: 14px; line-height: 1.5;">{{ $suggestion['desc'] }}</div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Action Buttons -->
                <div style="margin-top: 24px;">
                    @if(config('app.url'))
                    <a href="{{ config('app.url') }}/admin/logs" style="display: inline-block; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; margin-right: 8px; margin-bottom: 8px; background-color: #2563eb; color: #ffffff;">
                        📊 View Dashboard
                    </a>
                    @endif
                    
                    @if(!empty($logData['url']))
                    <a href="{{ $logData['url'] }}" style="display: inline-block; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; margin-right: 8px; margin-bottom: 8px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db;">
                        🔗 Reproduce Error
                    </a>
                    @endif
                    
                    <a href="mailto:dev@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'example.com' }}" style="display: inline-block; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; margin-right: 8px; margin-bottom: 8px; background-color: #ffffff; color: #374151; border: 1px solid #d1d5db;">
                        📧 Contact Team
                    </a>
                </div>
            </div>

            <!-- Event Timeline -->
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        ⏰
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">Event Timeline</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Chronological sequence of events</p>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div style="margin-top: 20px; position: relative; padding-left: 24px;">
                    
                    <div style="margin-bottom: 16px; position: relative;">
                        <div style="position: absolute; left: -18px; top: 6px; width: 8px; height: 8px; border-radius: 50%; background-color: #2563eb; border: 2px solid #ffffff;"></div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">
                            {{ date('H:i:s', strtotime($logData['datetime'] ?? 'now')) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 14px; color: #6b7280;">
                            <strong>{{ strtoupper($logData['level'] ?? 'ERROR') }} Event Occurred</strong><br>
                            {{ $logData['message'] ?? 'An error occurred' }}
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 16px; position: relative;">
                        <div style="position: absolute; left: -18px; top: 6px; width: 8px; height: 8px; border-radius: 50%; background-color: #2563eb; border: 2px solid #ffffff;"></div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">
                            {{ date('H:i:s', strtotime('-5 seconds', strtotime($logData['datetime'] ?? 'now'))) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 14px; color: #6b7280;">
                            Request initiated from {{ $logData['ip_address'] ?? 'unknown IP' }}
                        </div>
                    </div>
                    
                    @if(!empty($logData['execution_time']))
                    <div style="margin-bottom: 16px; position: relative;">
                        <div style="position: absolute; left: -18px; top: 6px; width: 8px; height: 8px; border-radius: 50%; background-color: #2563eb; border: 2px solid #ffffff;"></div>
                        <div style="font-size: 12px; color: #6b7280; font-weight: 600; text-transform: uppercase;">
                            {{ date('H:i:s', strtotime('-' . ($logData['execution_time']/1000) . ' seconds', strtotime($logData['datetime'] ?? 'now'))) }}
                        </div>
                        <div style="margin-top: 4px; font-size: 14px; color: #6b7280;">
                            Processing started ({{ $logData['execution_time'] }}ms duration)
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- System Information -->
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                
                <!-- Section Header -->
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #f3f4f6; border: 2px solid #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 12px; flex-shrink: 0;">
                        ⚙️
                    </div>
                    <div>
                        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0 0 4px 0;">System Information</h2>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Application and server details</p>
                    </div>
                </div>
                
                <!-- System Info Cards -->
                <table style="width: 100%; border-collapse: separate; border-spacing: 8px;">
                    <tr>
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Laravel Version</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ app()->version() }}</div>
                        </td>
                        
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">PHP Version</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ phpversion() }}</div>
                        </td>
                        
                        @if(!empty($logData['server_name']))
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Server</div>
                            <div style="font-family: 'Courier New', Monaco, monospace; background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb; font-weight: 500; font-size: 14px; color: #111827;">
                                {{ $logData['server_name'] }}
                            </div>
                        </td>
                        @else
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Server</div>
                            <div style="font-family: 'Courier New', Monaco, monospace; background-color: #f3f4f6; padding: 4px 8px; border-radius: 4px; border: 1px solid #e5e7eb; font-weight: 500; font-size: 14px; color: #111827;">
                                app-server-01
                            </div>
                        </td>
                        @endif
                        
                        <td style="background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; vertical-align: top; width: 25%;">
                            <div style="font-size: 12px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">Timestamp</div>
                            <div style="font-size: 14px; font-weight: 600; color: #111827;">{{ $logData['datetime'] ?? now()->toISOString() }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Footer -->
            <div style="background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px; text-align: center;">
                <div style="color: #6b7280; font-size: 14px; margin-bottom: 16px;">
                    <strong>{{ config('app.name') }}</strong> Error Monitoring System<br>
                    This alert was automatically generated when a {{ strtolower($logData['level'] ?? 'error') }} level event was detected.
                </div>
                <div style="text-align: center;">
                    @if(config('app.url'))
                    <a href="{{ config('app.url') }}/admin" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 14px; margin: 0 12px;">📊 Dashboard</a>
                    @endif
                    <a href="{{ config('app.url') }}/docs" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 14px; margin: 0 12px;">📚 Documentation</a>
                    <a href="{{ config('app.url') }}/settings" style="color: #2563eb; text-decoration: none; font-weight: 600; font-size: 14px; margin: 0 12px;">⚙️ Settings</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>