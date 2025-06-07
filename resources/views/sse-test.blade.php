<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSE Debug Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .connected { background: #d4edda; color: #155724; }
        .disconnected { background: #f8d7da; color: #721c24; }
        .log-item { 
            padding: 10px; 
            margin: 5px 0; 
            border-left: 4px solid #007bff; 
            background: #f8f9fa;
            font-family: monospace;
            font-size: 12px;
        }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .connect { background: #28a745; color: white; border: none; }
        .disconnect { background: #dc3545; color: white; border: none; }
        .clear { background: #6c757d; color: white; border: none; }
        .test { background: #007bff; color: white; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 SSE Debug Test</h1>
        
        <div id="status" class="status disconnected">
            Status: Disconnected
        </div>
        
        <div>
            <button id="connect" class="connect">Connect</button>
            <button id="disconnect" class="disconnect">Disconnect</button>
            <button id="clear" class="clear">Clear</button>
            <button id="test-log" class="test">Generate Test Log</button>
        </div>
        
        <h3>Raw SSE Messages:</h3>
        <div id="messages" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;"></div>
        
        <h3>Connection Info:</h3>
        <pre id="connection-info">Not connected</pre>
    </div>

    <script>
        let eventSource = null;
        let messageCount = 0;
        
        const statusEl = document.getElementById('status');
        const messagesEl = document.getElementById('messages');
        const connectionInfoEl = document.getElementById('connection-info');
        
        // Replace with your actual API key
        const API_KEY = 'your-api-key-here';
        
        function updateStatus(status, message) {
            statusEl.className = `status ${status}`;
            statusEl.textContent = `Status: ${message}`;
        }
        
        function addMessage(type, data, event = null) {
            messageCount++;
            const div = document.createElement('div');
            div.className = `log-item ${type}`;
            
            const timestamp = new Date().toLocaleTimeString();
            div.innerHTML = `
                <strong>[${messageCount}] ${timestamp} - ${type.toUpperCase()}</strong><br>
                ${event ? `Event: ${event}<br>` : ''}
                Data: ${typeof data === 'string' ? data : JSON.stringify(data, null, 2)}
            `;
            
            messagesEl.appendChild(div);
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
        
        function updateConnectionInfo() {
            if (eventSource) {
                const info = {
                    readyState: eventSource.readyState,
                    readyStateText: ['CONNECTING', 'OPEN', 'CLOSED'][eventSource.readyState],
                    url: eventSource.url,
                    withCredentials: eventSource.withCredentials
                };
                connectionInfoEl.textContent = JSON.stringify(info, null, 2);
            } else {
                connectionInfoEl.textContent = 'Not connected';
            }
        }
        
        function connect() {
            if (eventSource) {
                eventSource.close();
            }
            
            // Build URL with API key
            const url = `/log-management/stream?key=${API_KEY}&include_recent=true`;
            console.log('Connecting to:', url);
            
            eventSource = new EventSource(url);
            updateConnectionInfo();
            
            eventSource.onopen = function(event) {
                updateStatus('connected', 'Connected');
                addMessage('info', 'Connection opened', 'onopen');
                updateConnectionInfo();
            };
            
            eventSource.onmessage = function(event) {
                addMessage('info', event.data, 'onmessage');
                updateConnectionInfo();
            };
            
            eventSource.onerror = function(event) {
                updateStatus('disconnected', 'Connection error');
                addMessage('error', 'Connection error occurred', 'onerror');
                updateConnectionInfo();
            };
            
            // Listen to specific events
            ['connection', 'log', 'heartbeat', 'statistics', 'status', 'error', 'disconnect'].forEach(eventType => {
                eventSource.addEventListener(eventType, function(event) {
                    addMessage('info', event.data, eventType);
                });
            });
        }
        
        function disconnect() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
            updateStatus('disconnected', 'Disconnected');
            updateConnectionInfo();
        }
        
        function clearMessages() {
            messagesEl.innerHTML = '';
            messageCount = 0;
        }
        
        function generateTestLog() {
            // Send a test log via fetch
            fetch('/log-management/api/test-log', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Log-Management-Key': API_KEY
                },
                body: JSON.stringify({
                    level: 'error',
                    message: 'Test log from SSE debug page',
                    context: { test: true, timestamp: new Date().toISOString() }
                })
            }).then(response => {
                if (response.ok) {
                    addMessage('info', 'Test log generated successfully');
                } else {
                    addMessage('error', `Failed to generate test log: ${response.status}`);
                }
            }).catch(error => {
                addMessage('error', `Error generating test log: ${error.message}`);
            });
        }
        
        // Event listeners
        document.getElementById('connect').addEventListener('click', connect);
        document.getElementById('disconnect').addEventListener('click', disconnect);
        document.getElementById('clear').addEventListener('click', clearMessages);
        document.getElementById('test-log').addEventListener('click', generateTestLog);
        
        // Auto-connect on page load (uncomment to enable)
        // connect();
        
        // Update connection info every second
        setInterval(updateConnectionInfo, 1000);
    </script>
</body>