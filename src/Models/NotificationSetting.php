<?php

namespace Fulgid\LogManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $table = 'notification_settings';

    protected $fillable = [
        'channel',
        'enabled',
        'settings',
        'conditions',
        'rate_limit',
        'last_notification_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
        'conditions' => 'array',
        'rate_limit' => 'array',
        'last_notification_at' => 'datetime',
    ];

    protected $attributes = [
        'enabled' => true,
        'settings' => '{}',
        'conditions' => '{}',
        'rate_limit' => '{}',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure channel name is always lowercase
        static::creating(function ($setting) {
            $setting->channel = strtolower($setting->channel);
        });

        static::updating(function ($setting) {
            $setting->channel = strtolower($setting->channel);
        });
    }

    /**
     * Scope a query to only include enabled settings.
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to only include settings for a specific channel.
     */
    public function scopeForChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', strtolower($channel));
    }

    /**
     * Get setting value by key.
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Set setting value by key.
     */
    public function setSetting(string $key, $value): self
    {
        $settings = $this->settings;
        $settings[$key] = $value;
        $this->settings = $settings;
        
        return $this;
    }

    /**
     * Get condition value by key.
     */
    public function getCondition(string $key, $default = null)
    {
        return $this->conditions[$key] ?? $default;
    }

    /**
     * Set condition value by key.
     */
    public function setCondition(string $key, $value): self
    {
        $conditions = $this->conditions;
        $conditions[$key] = $value;
        $this->conditions = $conditions;
        
        return $this;
    }

    /**
     * Check if notification should be sent based on conditions.
     */
    public function shouldNotify(array $logData): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Check rate limiting
        if (!$this->passesRateLimit()) {
            return false;
        }

        // Check log level conditions
        if (!$this->passesLevelCondition($logData)) {
            return false;
        }

        // Check environment conditions
        if (!$this->passesEnvironmentCondition()) {
            return false;
        }

        // Check custom conditions
        if (!$this->passesCustomConditions($logData)) {
            return false;
        }

        return true;
    }

    /**
     * Check if rate limit allows notification.
     */
    public function passesRateLimit(): bool
    {
        $rateLimitConfig = $this->rate_limit;

        if (empty($rateLimitConfig) || !isset($rateLimitConfig['enabled']) || !$rateLimitConfig['enabled']) {
            return true;
        }

        $maxPerMinute = $rateLimitConfig['max_per_minute'] ?? 10;
        $windowMinutes = $rateLimitConfig['window_minutes'] ?? 1;

        if (!$this->last_notification_at) {
            return true;
        }

        $timeSinceLastNotification = now()->diffInMinutes($this->last_notification_at);
        
        return $timeSinceLastNotification >= $windowMinutes;
    }

    /**
     * Check if log level passes conditions.
     */
    public function passesLevelCondition(array $logData): bool
    {
        $allowedLevels = $this->getCondition('levels', ['error', 'critical', 'emergency']);
        
        return in_array(strtolower($logData['level']), array_map('strtolower', $allowedLevels));
    }

    /**
     * Check if current environment passes conditions.
     */
    public function passesEnvironmentCondition(): bool
    {
        $allowedEnvironments = $this->getCondition('environments', ['production']);
        
        return in_array(app()->environment(), $allowedEnvironments);
    }

    /**
     * Check custom conditions.
     */
    public function passesCustomConditions(array $logData): bool
    {
        $customConditions = $this->getCondition('custom', []);

        foreach ($customConditions as $condition) {
            if (!$this->evaluateCondition($condition, $logData)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition.
     */
    protected function evaluateCondition(array $condition, array $logData): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';

        $fieldValue = $this->getFieldValue($field, $logData);

        switch ($operator) {
            case 'equals':
                return $fieldValue == $value;
            case 'not_equals':
                return $fieldValue != $value;
            case 'contains':
                return str_contains((string) $fieldValue, $value);
            case 'not_contains':
                return !str_contains((string) $fieldValue, $value);
            case 'starts_with':
                return str_starts_with((string) $fieldValue, $value);
            case 'ends_with':
                return str_ends_with((string) $fieldValue, $value);
            case 'regex':
                return preg_match($value, (string) $fieldValue);
            default:
                return true;
        }
    }

    /**
     * Get field value from log data.
     */
    protected function getFieldValue(string $field, array $logData)
    {
        // Support dot notation for nested arrays
        $keys = explode('.', $field);
        $value = $logData;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Update last notification timestamp.
     */
    public function markAsNotified(): void
    {
        $this->update(['last_notification_at' => now()]);
    }

    /**
     * Get default settings for a channel.
     */
    public static function getDefaultSettings(string $channel): array
    {
        $defaults = [
            'email' => [
                'to' => config('log-management.notifications.channels.email.to'),
                'from' => config('log-management.notifications.channels.email.from'),
                'subject_prefix' => '[LOG ALERT]',
            ],
            'slack' => [
                'webhook_url' => config('log-management.notifications.channels.slack.webhook_url'),
                'channel' => config('log-management.notifications.channels.slack.channel', '#alerts'),
                'username' => config('log-management.notifications.channels.slack.username', 'Log Management'),
                'icon_emoji' => config('log-management.notifications.channels.slack.icon_emoji', ':warning:'),
            ],
            'webhook' => [
                'url' => config('log-management.notifications.channels.webhook.url'),
                'method' => config('log-management.notifications.channels.webhook.method', 'POST'),
                'headers' => config('log-management.notifications.channels.webhook.headers', []),
                'secret' => config('log-management.notifications.channels.webhook.secret'),
            ],
        ];

        return $defaults[$channel] ?? [];
    }

    /**
     * Create or update settings for a channel.
     */
    public static function updateSettings(string $channel, array $settings, array $conditions = []): self
    {
        return static::updateOrCreate(
            ['channel' => strtolower($channel)],
            [
                'settings' => array_merge(static::getDefaultSettings($channel), $settings),
                'conditions' => $conditions,
            ]
        );
    }
}