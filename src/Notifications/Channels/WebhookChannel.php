<?php

namespace Fulgid\LogManagement\Notifications\Channels;

use Fulgid\LogManagement\Notifications\Contracts\NotificationChannelInterface;
use Fulgid\LogManagement\Models\NotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel implements NotificationChannelInterface
{
    protected string $name = 'webhook';

    /**
     * Send a notification through webhook.
     */
    public function send(array $logData): bool
    {
        try {
            $setting = NotificationSetting::forChannel($this->name)->first();
            
            if (!$setting || !$setting->shouldNotify($logData)) {
                return false;
            }

            $url = $setting->getSetting('url') ?? 
                   config('log-management.notifications.channels.webhook.url');

            if (!$url) {
                Log::channel('single')->warning('Webhook notification skipped: No URL configured');
                return false;
            }

            $method = strtoupper($setting->getSetting('method', 'POST'));
            $headers = $this->buildHeaders($setting);
            $payload = $this->buildPayload($logData, $setting);

            $httpClient = Http::timeout(
                $setting->getSetting('timeout', 10)
            )->withHeaders($headers);

            // Add authentication if configured
            $this->addAuthentication($httpClient, $setting);

            $response = match ($method) {
                'GET' => $httpClient->get($url, $payload),
                'PUT' => $httpClient->put($url, $payload),
                'PATCH' => $httpClient->patch($url, $payload),
                default => $httpClient->post($url, $payload),
            };

            if ($response->successful()) {
                $setting->markAsNotified();
                return true;
            }

            Log::channel('single')->error('Webhook notification failed: ' . $response->body(), [
                'status' => $response->status(),
                'url' => $url,
            ]);
            return false;
        } catch (\Exception $e) {
            Log::channel('single')->error('Failed to send webhook notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if this notification channel is enabled.
     */
    public function isEnabled(): bool
    {
        if (!config('log-management.notifications.channels.webhook.enabled', false)) {
            return false;
        }

        $setting = NotificationSetting::forChannel($this->name)->first();
        
        return $setting ? $setting->enabled : true;
    }

    /**
     * Get the channel name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Validate the channel configuration.
     */
    public function validateConfiguration(): bool
    {
        $setting = NotificationSetting::forChannel($this->name)->first();
        $url = $setting?->getSetting('url') ?? 
               config('log-management.notifications.channels.webhook.url');
        
        return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Get the channel configuration requirements.
     */
    public function getConfigurationRequirements(): array
    {
        return [
            'url' => 'Webhook URL',
            'method' => 'HTTP method (optional, defaults to POST)',
            'headers' => 'Additional headers (optional)',
            'auth_type' => 'Authentication type: none, bearer, basic, api_key (optional)',
            'auth_token' => 'Authentication token (required if auth_type is set)',
            'secret' => 'Secret for signature verification (optional)',
            'timeout' => 'Request timeout in seconds (optional, defaults to 10)',
        ];
    }

    /**
     * Test the channel connectivity.
     */
    public function testConnection(): array
    {
        try {
            if (!$this->validateConfiguration()) {
                return [
                    'success' => false,
                    'message' => 'Invalid webhook configuration',
                ];
            }

            $testLogData = [
                'message' => 'Test notification from Log Management Package',
                'level' => 'info',
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
                'context' => ['test' => true],
            ];

            $result = $this->send($testLogData);

            return [
                'success' => $result,
                'message' => $result ? 'Test webhook sent successfully' : 'Failed to send test webhook',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build HTTP headers for the webhook request.
     */
    protected function buildHeaders(?NotificationSetting $setting = null): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'LogManagement/1.0',
        ];

        // Add custom headers from settings
        $customHeaders = $setting?->getSetting('headers', []) ?? 
                        config('log-management.notifications.channels.webhook.headers', []);

        return array_merge($headers, $customHeaders);
    }

    /**
     * Build the webhook payload.
     */
    protected function buildPayload(array $logData, ?NotificationSetting $setting = null): array
    {
        $payload = [
            'event' => 'log.notification',
            'timestamp' => now()->toISOString(),
            'log' => [
                'level' => $logData['level'],
                'message' => $logData['message'],
                'timestamp' => $logData['timestamp'],
                'environment' => $logData['environment'],
                'context' => $logData['context'] ?? [],
                'extra' => $logData['extra'] ?? [],
            ],
            'application' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'environment' => app()->environment(),
            ],
        ];

        // Add request information if available
        if (!empty($logData['url'])) {
            $payload['request'] = [
                'url' => $logData['url'],
                'user_agent' => $logData['user_agent'] ?? null,
                'ip' => $logData['ip'] ?? null,
            ];
        }

        // Add signature if secret is configured
        $secret = $setting?->getSetting('secret') ?? 
                 config('log-management.notifications.channels.webhook.secret');

        if ($secret) {
            $payload['signature'] = $this->generateSignature($payload, $secret);
        }

        return $payload;
    }

    /**
     * Add authentication to the HTTP client.
     */
    protected function addAuthentication($httpClient, ?NotificationSetting $setting = null): void
    {
        $authType = $setting?->getSetting('auth_type') ?? 
                   config('log-management.notifications.channels.webhook.auth_type');
        
        $authToken = $setting?->getSetting('auth_token') ?? 
                    config('log-management.notifications.channels.webhook.auth_token');

        if (!$authType || !$authToken) {
            return;
        }

        switch (strtolower($authType)) {
            case 'bearer':
                $httpClient->withToken($authToken);
                break;
            
            case 'basic':
                $credentials = explode(':', $authToken, 2);
                if (count($credentials) === 2) {
                    $httpClient->withBasicAuth($credentials[0], $credentials[1]);
                }
                break;
            
            case 'api_key':
                $keyName = $setting?->getSetting('api_key_name', 'X-API-Key');
                $httpClient->withHeaders([$keyName => $authToken]);
                break;
        }
    }

    /**
     * Generate signature for webhook verification.
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        return hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}