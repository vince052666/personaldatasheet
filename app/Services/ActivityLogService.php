<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SecurityEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class ActivityLogService
{
    public function logLogin(int $userId): ActivityLog
    {
        return ActivityLog::logActivity('login', $userId, 'User logged in');
    }

    public function logLogout(int $userId): ActivityLog
    {
        return ActivityLog::logActivity('logout', $userId, 'User logged out');
    }

    public function logFailedLogin(string $email, string $reason = 'Invalid credentials'): ActivityLog
    {
        $log = ActivityLog::logActivity(
            'failed_login',
            null,
            "Failed login attempt for: {$email}",
            ['email' => $email, 'reason' => $reason]
        );

        // Check for brute force
        $this->checkBruteForce(request()->ip());

        return $log;
    }

    public function logAccess(string $resource, int $resourceId, string $action = 'view'): ActivityLog
    {
        return ActivityLog::logActivity(
            "access.{$action}",
            auth()->id(),
            "Accessed {$resource} (ID: {$resourceId})",
            [
                'resource' => $resource,
                'resource_id' => $resourceId,
                'action' => $action,
            ]
        );
    }

    public function logExport(string $exportType, array $metadata = []): ActivityLog
    {
        return ActivityLog::logActivity(
            'export',
            auth()->id(),
            "Exported {$exportType}",
            array_merge(['export_type' => $exportType], $metadata)
        );
    }

    public function logDataModification(string $model, int $modelId, string $action, array $changes = []): ActivityLog
    {
        return ActivityLog::logActivity(
            "data.{$action}",
            auth()->id(),
            "Modified {$model} (ID: {$modelId})",
            [
                'model' => $model,
                'model_id' => $modelId,
                'action' => $action,
                'changes' => $changes,
            ]
        );
    }

    private function checkBruteForce(string $ip): void
    {
        $key = "failed_login:{$ip}";
        $attempts = RateLimiter::hit($key, 900); // 15 minutes

        if ($attempts >= 5) {
            $this->createSecurityEvent(
                'brute_force',
                'high',
                "Multiple failed login attempts from IP: {$ip}",
                ['ip_address' => $ip, 'attempts' => $attempts]
            );
        }
    }

    public function createSecurityEvent(
        string $eventType,
        string $severity,
        string $description,
        array $metadata = []
    ): SecurityEvent {
        return SecurityEvent::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'description' => $description,
            'metadata' => $metadata,
            'detected_at' => now(),
        ]);
    }

    public function getRecentActivities(?int $userId = null, int $limit = 50)
    {
        $query = ActivityLog::with('user')
            ->orderBy('performed_at', 'desc')
            ->limit($limit);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    public function getSecurityEvents(bool $unresolvedOnly = false, ?string $severity = null)
    {
        $query = SecurityEvent::with('user')->orderBy('detected_at', 'desc');

        if ($unresolvedOnly) {
            $query->unresolved();
        }

        if ($severity) {
            $query->bySeverity($severity);
        }

        return $query->get();
    }

    public function resolveSecurityEvent(int $eventId): void
    {
        SecurityEvent::findOrFail($eventId)->update(['resolved' => true]);
    }
}
