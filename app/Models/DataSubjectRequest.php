<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataSubjectRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'personal_data_sheet_id',
        'request_type',
        'requester_name',
        'requester_email',
        'requester_id_number',
        'request_details',
        'status',
        'response_notes',
        'processed_by',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function personalDataSheet(): BelongsTo
    {
        return $this->belongsTo(PersonalDataSheet::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function markAsProcessing(User $user): void
    {
        $this->update([
            'status' => 'processing',
            'processed_by' => $user->id,
        ]);
    }

    public function markAsCompleted(string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'response_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'response_notes' => $reason,
            'completed_at' => now(),
        ]);
    }
}
