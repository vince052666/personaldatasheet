<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Set tenant context for current request
        if (auth()->check()) {
            $user = auth()->user();
            
            // Store agency context in config for easy access
            config(['app.current_agency_id' => $user->agency_id]);
            config(['app.current_agency' => $user->agency]);
            
            // Set cache prefix for agency isolation
            if ($user->agency_id) {
                config(['cache.prefix' => 'agency_' . $user->agency_id . '_']);
            }
        }

        return $next($request);
    }
}
