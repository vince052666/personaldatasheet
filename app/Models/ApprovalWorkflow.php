<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'personal_data_sheet_id',
        'workflow_type',
        'status',
        'submitted_by',
        'approved_by',
        'assigned_to',
        'comments',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function history(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
