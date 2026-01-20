<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    private ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log the activity after the response
        if (auth()->check()) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    private function logActivity(Request $request, Response $response): void
    {
        $path = $request->path();
        $method = $request->method();
        
        // Determine activity type
        $activityType = $this->determineActivityType($method, $path);

        if ($activityType) {
            $this->activityLogService->logAccess(
                $this->getResourceFromPath($path),
                $this->getResourceIdFromPath($path, $request),
                $activityType
            );
        }
    }

    private function determineActivityType(string $method, string $path): ?string
    {
        return match($method) {
            'GET' => str_contains($path, 'export') ? 'export' : 'view',
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => null,
        };
    }

    private function getResourceFromPath(string $path): string
    {
        $segments = explode('/', $path);
        return $segments[0] ?? 'unknown';
    }

    private function getResourceIdFromPath(string $path, Request $request): int
    {
        // Try to get ID from route parameters
        $id = $request->route('id') ?? $request->route('pds') ?? 0;
        return is_numeric($id) ? (int) $id : 0;
    }
}
