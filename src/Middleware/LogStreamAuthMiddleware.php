<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogStreamAuthMiddleware
{
    /**
     * Handle an incoming request for log streaming.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Implement your authentication logic here
        // For example, check if the user is authenticated or has the right permissions

        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}