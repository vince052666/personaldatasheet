<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RoleBasedRateLimiting
{
    private const RATE_LIMITS = [
        'admin' => 1000, // requests per minute
        'hr' => 500,
        'user' => 100,
        'guest' => 60,
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $role = $user ? $this->getUserRole($user) : 'guest';
        $limit = self::RATE_LIMITS[$role] ?? self::RATE_LIMITS['guest'];

        $key = $this->resolveRequestSignature($request, $role);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($key, 60); // 60 seconds decay

        $response = $next($request);

        return $this->addRateLimitHeaders($response, $key, $limit);
    }

    private function getUserRole($user): string
    {
        // Get the highest priority role
        $roles = $user->roles->pluck('name')->toArray();

        if (in_array('admin', $roles)) return 'admin';
        if (in_array('hr', $roles)) return 'hr';
        return 'user';
    }

    private function resolveRequestSignature(Request $request, string $role): string
    {
        return sha1($role . '|' . $request->ip());
    }

    private function addRateLimitHeaders(Response $response, string $key, int $limit): Response
    {
        $remaining = RateLimiter::remaining($key, $limit);
        $retryAfter = RateLimiter::availableIn($key);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        
        if ($remaining === 0) {
            $response->headers->set('Retry-After', $retryAfter);
            $response->headers->set('X-RateLimit-Reset', now()->addSeconds($retryAfter)->timestamp);
        }

        return $response;
    }
}
