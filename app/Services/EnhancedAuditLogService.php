<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;

class EnhancedAuditLogService
{
    private ?string $previousSignature = null;
    private int $chainSequence = 0;

    public function __construct()
    {
        $this->initializeChain();
    }

    public function log(
        string $action,
        string $auditable_type,
        ?int $auditable_id,
        ?array $old_values = null,
        ?array $new_values = null,
        ?array $metadata = null
    ): AuditLog {
        $log = AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable_type,
            'auditable_id' => $auditable_id,
            'old_values' => $old_values,
            'new_values' => $new_values,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
            'previous_signature' => $this->previousSignature,
            'chain_sequence' => ++$this->chainSequence,
        ]);

        // Generate and save signature
        $signature = $this->generateSignature($log);
        $chainHash = $this->generateChainHash($log);
        
        $log->update([
            'signature' => $signature,
            'chain_hash' => $chainHash,
        ]);

        $this->previousSignature = $signature;

        return $log;
    }

    private function generateSignature(AuditLog $log): string
    {
        $data = implode('|', [
            $log->id,
            $log->user_id,
            $log->action,
            $log->auditable_type,
            $log->auditable_id,
            json_encode($log->old_values),
            json_encode($log->new_values),
            $log->created_at->timestamp,
            $log->chain_sequence,
        ]);

        return hash_hmac('sha256', $data, config('app.key'));
    }

    private function generateChainHash(AuditLog $log): string
    {
        $data = implode('|', [
            $log->signature,
            $log->previous_signature ?? '',
            $log->chain_sequence,
        ]);

        return hash('sha256', $data);
    }

    public function verifyIntegrity(AuditLog $log): bool
    {
        $expectedSignature = $this->generateSignature($log);
        $expectedChainHash = $this->generateChainHash($log);

        return $log->signature === $expectedSignature && 
               $log->chain_hash === $expectedChainHash;
    }

    public function verifyChain(?int $startId = null, ?int $endId = null): array
    {
        $query = AuditLog::orderBy('chain_sequence');

        if ($startId) {
            $query->where('id', '>=', $startId);
        }

        if ($endId) {
            $query->where('id', '<=', $endId);
        }

        $logs = $query->get();
        $results = [];
        $previousSignature = null;

        foreach ($logs as $log) {
            $isValid = $this->verifyIntegrity($log);
            $chainValid = $log->previous_signature === $previousSignature;

            $results[] = [
                'id' => $log->id,
                'sequence' => $log->chain_sequence,
                'signature_valid' => $isValid,
                'chain_valid' => $chainValid,
                'overall_valid' => $isValid && $chainValid,
            ];

            $previousSignature = $log->signature;
        }

        return $results;
    }

    public function detectTampering(): array
    {
        $verification = $this->verifyChain();
        return array_filter($verification, fn($result) => !$result['overall_valid']);
    }

    public function generateComplianceReport(?\DateTime $startDate = null, ?\DateTime $endDate = null): array
    {
        $query = AuditLog::query();

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->get();

        return [
            'total_entries' => $logs->count(),
            'date_range' => [
                'start' => $startDate ? $startDate->format('Y-m-d') : $logs->min('created_at'),
                'end' => $endDate ? $endDate->format('Y-m-d') : $logs->max('created_at'),
            ],
            'actions_breakdown' => $logs->groupBy('action')->map->count(),
            'users_activity' => $logs->groupBy('user_id')->map->count(),
            'integrity_status' => $this->verifyChain($logs->min('id'), $logs->max('id')),
            'tampering_detected' => !empty($this->detectTampering()),
        ];
    }

    private function initializeChain(): void
    {
        $lastLog = AuditLog::orderBy('chain_sequence', 'desc')->first();
        
        if ($lastLog) {
            $this->previousSignature = $lastLog->signature;
            $this->chainSequence = $lastLog->chain_sequence;
        }
    }

    public function exportAuditTrail(?\DateTime $startDate = null, ?\DateTime $endDate = null): string
    {
        $query = AuditLog::with('user');

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->orderBy('created_at')->get();

        $filename = 'audit-trail-' . now()->format('Y-m-d-His') . '.json';
        $path = storage_path('app/exports/' . $filename);

        \File::ensureDirectoryExists(dirname($path));

        $data = [
            'exported_at' => now()->toIso8601String(),
            'total_records' => $logs->count(),
            'integrity_verified' => empty($this->detectTampering()),
            'records' => $logs->toArray(),
        ];

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $path;
    }
}
