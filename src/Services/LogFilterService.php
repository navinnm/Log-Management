<?php

namespace Fulgid\LogManagement\Services;

class LogFilterService
{
    protected array $filters = [];

    /**
     * Add a filter function.
     */
    public function addFilter(callable $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * Remove a filter function.
     */
    public function removeFilter(callable $filter): void
    {
        $this->filters = array_filter($this->filters, function ($f) use ($filter) {
            return $f !== $filter;
        });
    }

    /**
     * Get all filters.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->filters = [];
    }

    /**
     * Check if log data should be processed.
     */
    public function shouldProcess(array $logData): bool
    {
        // TEMPORARILY DISABLE rate limiting to fix the cache permission issue
        // Apply rate limiting
        // if (!$this->passesRateLimit($logData)) {
        //     return false;
        // }

        // Apply environment filters
        if (!$this->passesEnvironmentFilter($logData)) {
            return false;
        }

        // Apply custom filters
        foreach ($this->filters as $filter) {
            try {
                if (!$filter($logData)) {
                    return false;
                }
            } catch (\Throwable $e) {
                // If filter fails, allow the log to proceed
                continue;
            }
        }

        return true;
    }

    /**
     * Check rate limiting for log processing.
     * TEMPORARILY DISABLED to fix cache permission issues
     */
    protected function passesRateLimit(array $logData): bool
    {
        $rateLimitConfig = config('log-management.rate_limit');
        
        if (!$rateLimitConfig['enabled']) {
            return true;
        }

        // TEMPORARILY SKIP rate limiting to avoid cache permission errors
        return true;

        // TODO: Re-enable this after fixing permissions:
        /*
        try {
            $cacheKey = 'log_management_rate_limit_' . md5($logData['message'] . $logData['level']);
            $currentCount = cache()->get($cacheKey, 0);

            if ($currentCount >= $rateLimitConfig['max_per_minute']) {
                return false;
            }

            cache()->put($cacheKey, $currentCount + 1, 60);
            
            return true;
        } catch (\Throwable $e) {
            // If cache fails, allow the log to proceed
            return true;
        }
        */
    }

    /**
     * Check environment-based filtering.
     */
    protected function passesEnvironmentFilter(array $logData): bool
    {
        $allowedEnvironments = config('log-management.environments', ['production', 'staging']);
        
        return in_array(app()->environment(), $allowedEnvironments);
    }
}