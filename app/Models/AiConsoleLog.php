<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConsoleLog extends Model
{
    protected $fillable = [
        'agency_id',
        'user_id',
        'session_id',
        'query_type',
        'prompt',
        'response',
        'context',
        'findings',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'actions_taken',
        'flagged_for_review',
    ];

    protected $casts = [
        'context' => 'array',
        'findings' => 'array',
        'actions_taken' => 'array',
        'flagged_for_review' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function needsReview(): bool
    {
        return $this->flagged_for_review && $this->isPending();
    }
}
