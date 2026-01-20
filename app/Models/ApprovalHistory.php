<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalHistory extends Model
{
    protected $table = 'approval_history';

    protected $fillable = [
        'approval_workflow_id',
        'action',
        'from_status',
        'to_status',
        'performed_by',
        'comments',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
