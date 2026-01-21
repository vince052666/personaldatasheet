<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        if ($request->has('model_type')) {
            $query->where('model_type', $request->get('model_type'));
        }

        if ($request->has('model_id')) {
            $query->where('model_id', $request->get('model_id'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->get('action'));
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($logs);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user');

        return response()->json($auditLog);
    }
}
