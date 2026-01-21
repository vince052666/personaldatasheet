<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $correlationId = $request->header('X-Correlation-ID') 
            ?? Str::uuid()->toString();

        $request->attributes->set('correlation_id', $correlationId);

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
