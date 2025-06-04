<?php

namespace Fulgid\LogManagement\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogManagementAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if authentication is disabled
        if (!config('log-management.auth.enabled', false)) {
            return $next($request);
        }

        // Check API key authentication
        if ($this->checkApiKey($request)) {
            return $next($request);
        }

        // Check user authentication
        if ($this->checkUserAuth($request)) {
            return $next($request);
        }

        // Check IP whitelist
        if ($this->checkIpWhitelist($request)) {
            return $next($request);
        }

        return $this->unauthorized($request);
    }

    /**
     * Check API key authentication.
     */
    protected function checkApiKey(Request $request): bool
    {
        $apiKey = $request->header('X-Log-Management-Key') ?? 
                  $request->header('Authorization') ?? 
                  $request->query('key');

        // Handle Bearer token format
        if (str_starts_with($apiKey ?? '', 'Bearer ')) {
            $apiKey = substr($apiKey, 7);
        }

        if (!$apiKey) {
            return false;
        }

        $validKeys = config('log-management.auth.api_keys', []);
        
        // Also check environment variables for API keys
        for ($i = 1; $i <= 10; $i++) {
            $envKey = env("LOG_MANAGEMENT_API_KEY_{$i}");
            if ($envKey) {
                $validKeys[] = $envKey;
            }
        }

        return in_array($apiKey, $validKeys);
    }

    /**
     * Check user authentication and permissions.
     */
    protected function checkUserAuth(Request $request): bool
    {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();
        
        // Check if user has required permission
        $requiredPermission = config('log-management.auth.permission', 'view-logs');
        
        // If no permission system is available, allow authenticated users
        if (!method_exists($user, 'can')) {
            return true;
        }

        return $user->can($requiredPermission);
    }

    /**
     * Check IP whitelist.
     */
    protected function checkIpWhitelist(Request $request): bool
    {
        $whitelist = config('log-management.auth.ip_whitelist', []);
        
        if (empty($whitelist)) {
            return false;
        }

        $clientIp = $request->ip();

        foreach ($whitelist as $allowedIp) {
            if ($this->ipMatches($clientIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if client IP matches allowed IP (supports CIDR notation).
     */
    protected function ipMatches(string $clientIp, string $allowedIp): bool
    {
        // Exact match
        if ($clientIp === $allowedIp) {
            return true;
        }

        // CIDR notation check
        if (str_contains($allowedIp, '/')) {
            return $this->cidrMatch($clientIp, $allowedIp);
        }

        // Wildcard support (basic)
        if (str_contains($allowedIp, '*')) {
            $pattern = str_replace('*', '.*', preg_quote($allowedIp, '/'));
            return preg_match("/^{$pattern}$/", $clientIp);
        }

        return false;
    }

    /**
     * Check if IP matches CIDR range.
     */
    protected function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->cidrMatchIPv4($ip, $subnet, (int) $mask);
        }
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->cidrMatchIPv6($ip, $subnet, (int) $mask);
        }
        
        return false;
    }

    /**
     * Check IPv4 CIDR match.
     */
    protected function cidrMatchIPv4(string $ip, string $subnet, int $mask): bool
    {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Check IPv6 CIDR match.
     */
    protected function cidrMatchIPv6(string $ip, string $subnet, int $mask): bool
    {
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }
        
        $bytes = $mask >> 3;
        $bits = $mask & 7;
        
        if ($bytes > 0) {
            if (substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                return false;
            }
        }
        
        if ($bits > 0 && $bytes < 16) {
            $mask = 0xFF << (8 - $bits);
            if ((ord($ipBin[$bytes]) & $mask) !== (ord($subnetBin[$bytes]) & $mask)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Access denied to log management system',
                'code' => 401,
            ], 401);
        }

        // For SSE requests, return a specific response
        if ($request->header('Accept') === 'text/event-stream') {
            return response("data: " . json_encode([
                'error' => 'Unauthorized',
                'message' => 'Access denied to log stream'
            ]) . "\n\n", 401, [
                'Content-Type' => 'text/event-stream',
            ]);
        }

        return response()->view('log-management::errors.unauthorized', [], 401);
    }

    /**
     * Get authentication information for debugging.
     */
    public function getAuthInfo(Request $request): array
    {
        return [
            'auth_enabled' => config('log-management.auth.enabled', false),
            'user_authenticated' => auth()->check(),
            'user_id' => auth()->id(),
            'api_key_provided' => !empty($request->header('X-Log-Management-Key')),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ];
    }
}