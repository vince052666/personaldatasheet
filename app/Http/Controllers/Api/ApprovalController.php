<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApprovalService;
use App\Models\ApprovalWorkflow;
use App\Models\PersonalDataSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    private ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $request->query('assigned_to_me') ? auth()->id() : null;
        $workflows = $this->approvalService->getPendingApprovals($userId);

        return response()->json([
            'data' => $workflows,
            'meta' => [
                'total' => $workflows->count(),
            ],
        ]);
    }

    public function submit(Request $request, int $pdsId): JsonResponse
    {
        $pds = PersonalDataSheet::findOrFail($pdsId);

        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $workflow = $this->approvalService->submitForApproval(
            $pds,
            $validated['assigned_to'] ?? null
        );

        return response()->json([
            'message' => 'PDS submitted for approval',
            'data' => $workflow,
        ], 201);
    }

    public function approve(Request $request, int $workflowId): JsonResponse
    {
        $workflow = ApprovalWorkflow::findOrFail($workflowId);

        $validated = $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        $this->approvalService->approve($workflow, $validated['comments'] ?? null);

        return response()->json([
            'message' => 'PDS approved successfully',
            'data' => $workflow->fresh(),
        ]);
    }

    public function reject(Request $request, int $workflowId): JsonResponse
    {
        $workflow = ApprovalWorkflow::findOrFail($workflowId);

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $this->approvalService->reject($workflow, $validated['reason']);

        return response()->json([
            'message' => 'PDS rejected',
            'data' => $workflow->fresh(),
        ]);
    }

    public function reassign(Request $request, int $workflowId): JsonResponse
    {
        $workflow = ApprovalWorkflow::findOrFail($workflowId);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $this->approvalService->reassign(
            $workflow,
            $validated['assigned_to'],
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Workflow reassigned successfully',
            'data' => $workflow->fresh(),
        ]);
    }
}
