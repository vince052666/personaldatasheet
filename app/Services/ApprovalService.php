<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalHistory;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ApprovalService
{
    public function submitForApproval(PersonalDataSheet $pds, ?int $assignToUserId = null): ApprovalWorkflow
    {
        return DB::transaction(function () use ($pds, $assignToUserId) {
            $workflow = ApprovalWorkflow::create([
                'personal_data_sheet_id' => $pds->id,
                'workflow_type' => 'pds_approval',
                'status' => 'pending_approval',
                'submitted_by' => auth()->id(),
                'assigned_to' => $assignToUserId ?? $this->getDefaultApprover(),
                'submitted_at' => now(),
            ]);

            $this->recordHistory($workflow, 'submitted', 'draft', 'pending_approval');

            // Notify assignee
            if ($workflow->assignee) {
                // Notification logic here
            }

            return $workflow;
        });
    }

    public function approve(ApprovalWorkflow $workflow, ?string $comments = null): void
    {
        DB::transaction(function () use ($workflow, $comments) {
            $workflow->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'comments' => $comments,
            ]);

            $this->recordHistory($workflow, 'approved', 'pending_approval', 'approved', $comments);

            // Update PDS status if needed
            $pds = $workflow->personalDataSheet;
            $pds->update(['status' => 'approved']);
        });
    }

    public function reject(ApprovalWorkflow $workflow, string $reason): void
    {
        DB::transaction(function () use ($workflow, $reason) {
            $workflow->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'rejected_at' => now(),
                'comments' => $reason,
            ]);

            $this->recordHistory($workflow, 'rejected', 'pending_approval', 'rejected', $reason);

            // Notify submitter
            if ($workflow->submitter) {
                // Notification logic here
            }
        });
    }

    public function reassign(ApprovalWorkflow $workflow, int $newAssigneeId, ?string $reason = null): void
    {
        DB::transaction(function () use ($workflow, $newAssigneeId, $reason) {
            $oldAssigneeId = $workflow->assigned_to;
            
            $workflow->update([
                'assigned_to' => $newAssigneeId,
            ]);

            $this->recordHistory($workflow, 'reassigned', null, null, $reason, [
                'old_assignee_id' => $oldAssigneeId,
                'new_assignee_id' => $newAssigneeId,
            ]);

            // Notify new assignee
        });
    }

    public function getPendingApprovals(?int $userId = null)
    {
        $query = ApprovalWorkflow::with(['personalDataSheet', 'submitter', 'assignee'])
            ->where('status', 'pending_approval');

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->orderBy('submitted_at', 'desc')->get();
    }

    private function recordHistory(
        ApprovalWorkflow $workflow,
        string $action,
        ?string $fromStatus = null,
        ?string $toStatus = null,
        ?string $comments = null,
        array $metadata = []
    ): ApprovalHistory {
        return ApprovalHistory::create([
            'approval_workflow_id' => $workflow->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'performed_by' => auth()->id(),
            'comments' => $comments,
            'metadata' => $metadata,
        ]);
    }

    private function getDefaultApprover(): ?int
    {
        // Get the first user with 'approver' or 'hr' role
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['approver', 'hr', 'admin']);
        })->first()?->id;
    }
}
