<?php

namespace App\Services;

use App\Models\RecordLock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\RecordLockedException;

class RecordLockService
{
    private const DEFAULT_LOCK_DURATION = 30; // minutes

    public function acquireLock(Model $model, ?int $userId = null, ?int $duration = null): RecordLock
    {
        $userId = $userId ?? auth()->id();
        $duration = $duration ?? self::DEFAULT_LOCK_DURATION;

        // Check if already locked
        $existingLock = $this->getActiveLock($model);
        
        if ($existingLock && $existingLock->locked_by !== $userId) {
            throw new RecordLockedException(
                "Record is locked by user {$existingLock->user->name} until {$existingLock->expires_at->format('Y-m-d H:i:s')}"
            );
        }

        // Clean expired locks
        $this->cleanExpiredLocks($model);

        // Create or update lock
        return RecordLock::updateOrCreate(
            [
                'lockable_type' => get_class($model),
                'lockable_id' => $model->id,
            ],
            [
                'locked_by' => $userId,
                'locked_at' => now(),
                'expires_at' => now()->addMinutes($duration),
                'session_id' => session()->getId(),
            ]
        );
    }

    public function releaseLock(Model $model, ?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();

        $lock = RecordLock::where('lockable_type', get_class($model))
            ->where('lockable_id', $model->id)
            ->where('locked_by', $userId)
            ->first();

        if ($lock) {
            return $lock->delete();
        }

        return false;
    }

    public function renewLock(Model $model, ?int $duration = null): RecordLock
    {
        $duration = $duration ?? self::DEFAULT_LOCK_DURATION;
        
        $lock = $this->getActiveLock($model);
        
        if (!$lock || $lock->locked_by !== auth()->id()) {
            throw new RecordLockedException("No active lock found or you don't own this lock");
        }

        $lock->update([
            'expires_at' => now()->addMinutes($duration),
        ]);

        return $lock;
    }

    public function getActiveLock(Model $model): ?RecordLock
    {
        return RecordLock::where('lockable_type', get_class($model))
            ->where('lockable_id', $model->id)
            ->active()
            ->first();
    }

    public function isLocked(Model $model): bool
    {
        return $this->getActiveLock($model) !== null;
    }

    public function isLockedByCurrentUser(Model $model): bool
    {
        $lock = $this->getActiveLock($model);
        return $lock && $lock->locked_by === auth()->id();
    }

    public function cleanExpiredLocks(?Model $model = null): int
    {
        $query = RecordLock::expired();

        if ($model) {
            $query->where('lockable_type', get_class($model))
                  ->where('lockable_id', $model->id);
        }

        return $query->delete();
    }

    public function forceRelease(Model $model): bool
    {
        return RecordLock::where('lockable_type', get_class($model))
            ->where('lockable_id', $model->id)
            ->delete();
    }
}
