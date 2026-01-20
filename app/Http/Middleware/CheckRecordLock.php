<?php

namespace App\Http\Middleware;

use App\Services\RecordLockService;
use App\Exceptions\RecordLockedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRecordLock
{
    private RecordLockService $lockService;

    public function __construct(RecordLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    public function handle(Request $request, Closure $next, string $modelClass): Response
    {
        // Only check for update/edit requests
        if (!in_array($request->method(), ['PUT', 'PATCH', 'POST'])) {
            return $next($request);
        }

        $id = $request->route('id') ?? $request->route('pds');
        
        if (!$id) {
            return $next($request);
        }

        try {
            $model = $modelClass::findOrFail($id);
            
            if ($this->lockService->isLocked($model) && !$this->lockService->isLockedByCurrentUser($model)) {
                $lock = $this->lockService->getActiveLock($model);
                
                return response()->json([
                    'error' => 'Record is currently locked',
                    'locked_by' => $lock->user->name,
                    'expires_at' => $lock->expires_at->toIso8601String(),
                ], 423); // 423 Locked
            }

            // Acquire lock for current user if not already locked
            if (!$this->lockService->isLockedByCurrentUser($model)) {
                $this->lockService->acquireLock($model);
            }

        } catch (RecordLockedException $e) {
            return response()->json(['error' => $e->getMessage()], 423);
        }

        return $next($request);
    }
}
