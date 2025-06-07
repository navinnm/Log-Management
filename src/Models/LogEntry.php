<?php

namespace Fulgid\LogManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class LogEntry extends Model
{
    use HasFactory;

    protected $table = 'log_entries';

    protected $fillable = [
        'message',
        'level',
        'channel',
        'context',
        'extra',
        'environment',
        'user_id',
        'session_id',
        'request_id',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'status_code',
        'execution_time',
        'memory_usage',
        'file_path',
        'line_number',
        'stack_trace',
        'tags',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'extra' => 'array',
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'execution_time' => 'float',
        'memory_usage' => 'integer',
        'status_code' => 'integer',
        'line_number' => 'integer',
    ];



    protected $attributes = [
        'context' => '{}',
        'extra' => '{}',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-cleanup old logs if enabled
        static::created(function ($logEntry) {
            if (config('log-management.database.auto_cleanup.enabled', false)) {
                $logEntry->cleanupOldLogs();
            }
        });
    }

    /**
     * Scope a query to only include logs of a given level.
     */
    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', strtolower($level));
    }

    /**
     * Scope a query to only include logs from a given channel.
     */
    public function scopeChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope a query to only include logs from today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope a query to only include logs from the last hour.
     */
    public function scopeLastHour(Builder $query): Builder
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    /**
     * Scope a query to only include logs within a date range.
     */
    public function scopeDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ]);
    }

    /**
     * Scope a query to only include error-level logs.
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->whereIn('level', ['error', 'critical', 'alert', 'emergency']);
    }

    /**
     * Scope a query to search within log messages.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('message', 'like', "%{$search}%")
              ->orWhere('context', 'like', "%{$search}%")
              ->orWhere('extra', 'like', "%{$search}%");
        });
    }

    /**
     * Get the log level with proper formatting.
     */
    public function getFormattedLevelAttribute(): string
    {
        return strtoupper($this->level);
    }

    /**
     * Get the log message truncated to a specific length.
     */
    public function getTruncatedMessageAttribute(): string
    {
        return strlen($this->message) > 100 
            ? substr($this->message, 0, 100) . '...' 
            : $this->message;
    }

    /**
     * Check if this is an error-level log.
     */
    public function isError(): bool
    {
        return in_array($this->level, ['error', 'critical', 'alert', 'emergency']);
    }

    /**
     * Check if this is a warning-level log.
     */
    public function isWarning(): bool
    {
        return $this->level === 'warning';
    }

    /**
     * Get context value by key.
     */
    public function getContextValue(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }

    /**
     * Get extra value by key.
     */
    public function getExtraValue(string $key, $default = null)
    {
        return $this->extra[$key] ?? $default;
    }

    /**
     * Clean up old log entries.
     */
    public function cleanupOldLogs(): void
    {
        $retentionDays = config('log-management.database.auto_cleanup.retention_days', 30);
        
        static::where('created_at', '<', now()->subDays($retentionDays))->delete();
    }

    /**
     * Get statistics for logs.
     */
    public static function getStats(int $days = 7): array
    {
        $fromDate = now()->subDays($days);

        return [
            'total' => static::where('created_at', '>=', $fromDate)->count(),
            'by_level' => static::where('created_at', '>=', $fromDate)
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'by_channel' => static::where('created_at', '>=', $fromDate)
                ->selectRaw('channel, COUNT(*) as count')
                ->groupBy('channel')
                ->pluck('count', 'channel')
                ->toArray(),
            'by_day' => static::where('created_at', '>=', $fromDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray(),
        ];
    }

    /**
     * Get recent error logs.
     */
    public static function getRecentErrors(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return static::errors()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Export logs to array format.
     */
    public function toExportArray(): array
    {
        return [
            'id' => $this->id,
            'timestamp' => $this->created_at->toISOString(),
            'level' => $this->level,
            'channel' => $this->channel,
            'message' => $this->message,
            'context' => $this->context,
            'extra' => $this->extra,
        ];
    }
}