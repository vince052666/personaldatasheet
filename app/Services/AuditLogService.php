<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public function log(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getLogsForModel(Model $model)
    {
        return AuditLog::where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLogsForUser(int $userId)
    {
        return AuditLog::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
