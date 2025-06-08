<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Log Management Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-tomorrow.min.css" rel="stylesheet">
    <style>
        .error-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 2rem 0;
        }
        .error-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .stack-trace {
            background: #2d3748;
            color: #e2e8f0;
            border-radius: 8px;
            max-height: 500px;
            overflow-y: auto;
        }
        .log-entry {
            border-left: 4px solid #dee2e6;
            transition: all 0.2s;
        }
        .log-entry:hover {
            background-color: #ffffff;
        }
        .log-error { border-left-color: #dc3545; }
        .log-warning { border-left-color: #ffc107; }
        .log-info { border-left-color: #17a2b8; }
        .error-id {
            font-family: 'Courier New', monospace;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .stats-card {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
        }
    </style>
</head>
<body class="error-content">
    <!-- Error Header -->
    <div class="error-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        {{ get_class($exception) }}
                    </h1>
                    <p class="lead mb-0">{{ $exception->getMessage() }}</p>
                    <small class="error-id">Error ID: {{ $errorId }}</small>
                </div>
                <div class="col-md-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stats-card p-3 rounded text-center">
                                <div class="h4 mb-0">{{ $stats['total_errors_today'] }}</div>
                                <small>Errors Today</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stats-card p-3 rounded text-center">
                                <div class="h4 mb-0">{{ $stats['total_logs_last_hour'] }}</div>
                                <small>Logs/Hour</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Error Details -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Error Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>File:</strong></div>
                            <div class="col-sm-9">
                                <code>{{ $exception->getFile() }}</code>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Line:</strong></div>
                            <div class="col-sm-9">
                                <span class="badge bg-danger">{{ $exception->getLine() }}</span>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>URL:</strong></div>
                            <div class="col-sm-9">
                                <code>{{ $request->fullUrl() }}</code>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-3"><strong>Method:</strong></div>
                            <div class="col-sm-9">
                                <span class="badge bg-info">{{ $request->method() }}</span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-3"><strong>IP:</strong></div>
                            <div class="col-sm-9">
                                <code>{{ $request->ip() }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('log-management.dashboard') }}" class="btn btn-primary">
                                <i class="fas fa-chart-line me-2"></i>View Dashboard
                            </a>
                            <a href="{{ route('log-management.api.logs.index', ['search' => substr($exception->getMessage(), 0, 20)]) }}" class="btn btn-info">
                                <i class="fas fa-search me-2"></i>Similar Errors
                            </a>
                            <button class="btn btn-success" onclick="markAsResolved()">
                                <i class="fas fa-check me-2"></i>Mark as Resolved
                            </button>
                            <button class="btn btn-warning" onclick="reportIssue()">
                                <i class="fas fa-flag me-2"></i>Report Issue
                            </button>
                        </div>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-server me-2"></i>System Info</h6>
                    </div>
                    <div class="card-body">
                        <small>
                            <div><strong>Memory:</strong> {{ round($stats['memory_usage']/1024/1024, 2) }}MB</div>
                            <div><strong>Peak:</strong> {{ round($stats['peak_memory']/1024/1024, 2) }}MB</div>
                            <div><strong>Time:</strong> {{ now()->format('Y-m-d H:i:s') }}</div>
                            <div><strong>Environment:</strong> {{ app()->environment() }}</div>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs for different sections -->
        <ul class="nav nav-tabs" id="errorTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stack-trace-tab" data-bs-toggle="tab" data-bs-target="#stack-trace" type="button">
                    Stack Trace
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recent-logs-tab" data-bs-toggle="tab" data-bs-target="#recent-logs" type="button">
                    Recent Logs
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="similar-errors-tab" data-bs-toggle="tab" data-bs-target="#similar-errors" type="button">
                    Similar Errors
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="request-data-tab" data-bs-toggle="tab" data-bs-target="#request-data" type="button">
                    Request Data
                </button>
            </li>
        </ul>

        <div class="tab-content" id="errorTabsContent">
            <!-- Stack Trace -->
            <div class="tab-pane fade show active" id="stack-trace" role="tabpanel">
                <div class="card border-top-0">
                    <div class="card-body p-0">
                        <pre class="stack-trace p-3 mb-0"><code>{{ $exception->getTraceAsString() }}</code></pre>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="tab-pane fade" id="recent-logs" role="tabpanel">
                <div class="card border-top-0">
                    <div class="card-header">
                        <h6 class="mb-0">Recent Logs (Last 5 minutes)</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($recentLogs as $log)
                        <div class="log-entry log-{{ $log->level }} p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="badge bg-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'info') }}">
                                        {{ strtoupper($log->level) }}
                                    </span>
                                    <span class="ms-2">{{ $log->message }}</span>
                                    <div class="text-muted small mt-1">{{ $log->channel }}</div>
                                </div>
                                <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                            </div>
                        </div>
                        @empty
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <div>No recent logs found</div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Similar Errors -->
            <div class="tab-pane fade" id="similar-errors" role="tabpanel">
                <div class="card border-top-0">
                    <div class="card-header">
                        <h6 class="mb-0">Similar Errors (Last 7 days)</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($similarErrors as $error)
                        <div class="log-entry log-error p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div>{{ $error->message }}</div>
                                    <small class="text-muted">{{ $error->created_at->diffForHumans() }}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewLogDetails({{ $error->id }})">
                                    View
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-smile fa-2x mb-2"></i>
                            <div>No similar errors found - this might be a new issue!</div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Request Data -->
            <div class="tab-pane fade" id="request-data" role="tabpanel">
                <div class="card border-top-0">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Headers</h6>
                                <pre class="bg-light p-2 rounded"><code>{{ json_encode($request->headers->all(), JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                            <div class="col-md-6">
                                <h6>Parameters</h6>
                                <pre class="bg-light p-2 rounded"><code>{{ json_encode($request->all(), JSON_PRETTY_PRINT) }}</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        function markAsResolved() {
            // Implementation for marking error as resolved
            alert('Error marked as resolved');
        }

        function reportIssue() {
            // Implementation for reporting issue
            alert('Issue reported to development team');
        }

        function viewLogDetails(logId) {
            // Implementation for viewing log details
            window.open(`{{ route('log-management.dashboard') }}/logs/${logId}`, '_blank');
        }

        // Auto-refresh recent logs every 30 seconds
        setInterval(function() {
            if (document.getElementById('recent-logs-tab').classList.contains('active')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>