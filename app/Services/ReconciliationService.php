<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\ImportBatch;
use App\Models\PersonalDataSheet;
use Illuminate\Support\Facades\DB;

class ReconciliationService
{
    public function generateImportReconciliation(ImportBatch $batch): array
    {
        return [
            'batch_id' => $batch->id,
            'batch_summary' => [
                'filename' => $batch->filename,
                'status' => $batch->status,
                'total_records' => $batch->total_records,
                'success_count' => $batch->success_count,
                'error_count' => $batch->error_count,
                'duplicate_count' => $batch->duplicate_count,
                'processed_records' => $batch->processed_records,
                'progress_percentage' => $batch->getProgressPercentage(),
            ],
            'error_breakdown' => $this->getErrorBreakdown($batch),
            'duplicate_analysis' => $this->getDuplicateAnalysis($batch),
            'data_quality_issues' => $this->getDataQualityIssues($batch),
            'field_completeness' => $this->getFieldCompleteness($batch),
        ];
    }

    protected function getErrorBreakdown(ImportBatch $batch): array
    {
        return DB::table('import_errors')
            ->where('import_batch_id', $batch->id)
            ->select('error_type', 'severity', DB::raw('COUNT(*) as count'))
            ->groupBy('error_type', 'severity')
            ->get()
            ->groupBy('error_type')
            ->map(function ($items) {
                return [
                    'total' => $items->sum('count'),
                    'by_severity' => $items->pluck('count', 'severity')->toArray(),
                ];
            })
            ->toArray();
    }

    protected function getDuplicateAnalysis(ImportBatch $batch): array
    {
        $duplicates = DB::table('import_staging')
            ->where('import_batch_id', $batch->id)
            ->where('status', 'duplicate')
            ->get();

        $duplicatesByKey = $duplicates->groupBy('duplicate_key')->map(function ($group) {
            return $group->count();
        })->toArray();

        return [
            'total_duplicates' => $duplicates->count(),
            'duplicate_keys' => $duplicatesByKey,
            'top_duplicates' => array_slice($duplicatesByKey, 0, 10, true),
        ];
    }

    protected function getDataQualityIssues(ImportBatch $batch): array
    {
        return [
            'missing_required_fields' => DB::table('import_errors')
                ->where('import_batch_id', $batch->id)
                ->where('error_type', 'validation')
                ->select('field', DB::raw('COUNT(*) as count'))
                ->groupBy('field')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'field')
                ->toArray(),
        ];
    }

    protected function getFieldCompleteness(ImportBatch $batch): array
    {
        $staging = DB::table('import_staging')
            ->where('import_batch_id', $batch->id)
            ->where('status', 'imported')
            ->get();

        if ($staging->isEmpty()) {
            return [];
        }

        $fields = ['email', 'mobile_no', 'telephone_no', 'date_of_birth', 'sex'];
        $completeness = [];

        foreach ($fields as $field) {
            $filledCount = $staging->filter(function ($record) use ($field) {
                $data = json_decode($record->mapped_data, true);
                return !empty($data[$field]);
            })->count();

            $completeness[$field] = [
                'filled' => $filledCount,
                'total' => $staging->count(),
                'percentage' => round(($filledCount / $staging->count()) * 100, 2),
            ];
        }

        return $completeness;
    }

    public function generateDuplicateReport(Agency $agency): array
    {
        $duplicates = PersonalDataSheet::where('agency_id', $agency->id)
            ->select('email', DB::raw('COUNT(*) as count'))
            ->whereNotNull('email')
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        $ssnDuplicates = PersonalDataSheet::where('agency_id', $agency->id)
            ->select('sss_no', DB::raw('COUNT(*) as count'))
            ->whereNotNull('sss_no')
            ->groupBy('sss_no')
            ->having('count', '>', 1)
            ->get();

        return [
            'email_duplicates' => $duplicates->count(),
            'ssn_duplicates' => $ssnDuplicates->count(),
            'total_potential_duplicates' => $duplicates->count() + $ssnDuplicates->count(),
            'duplicate_emails' => $duplicates->toArray(),
            'duplicate_ssn' => $ssnDuplicates->toArray(),
        ];
    }

    public function generateDataQualityScore(Agency $agency): array
    {
        $totalPds = PersonalDataSheet::where('agency_id', $agency->id)->count();

        if ($totalPds === 0) {
            return ['score' => 0, 'total_records' => 0];
        }

        $scores = [
            'completeness' => $this->calculateCompletenessScore($agency),
            'accuracy' => $this->calculateAccuracyScore($agency),
            'consistency' => $this->calculateConsistencyScore($agency),
        ];

        $overallScore = array_sum($scores) / count($scores);

        return [
            'overall_score' => round($overallScore, 2),
            'total_records' => $totalPds,
            'breakdown' => $scores,
            'grade' => $this->getGrade($overallScore),
        ];
    }

    protected function calculateCompletenessScore(Agency $agency): float
    {
        $requiredFields = ['surname', 'first_name', 'date_of_birth', 'sex', 'email'];
        $total = PersonalDataSheet::where('agency_id', $agency->id)->count();
        
        if ($total === 0) return 0;

        $completenessScores = [];
        foreach ($requiredFields as $field) {
            $filled = PersonalDataSheet::where('agency_id', $agency->id)
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->count();
            $completenessScores[] = ($filled / $total) * 100;
        }

        return array_sum($completenessScores) / count($completenessScores);
    }

    protected function calculateAccuracyScore(Agency $agency): float
    {
        // Check for valid email formats
        $totalWithEmail = PersonalDataSheet::where('agency_id', $agency->id)
            ->whereNotNull('email')
            ->count();

        if ($totalWithEmail === 0) return 100;

        // Use PHP validation instead of database-specific REGEXP
        $emails = PersonalDataSheet::where('agency_id', $agency->id)
            ->whereNotNull('email')
            ->pluck('email');
        
        $validEmails = $emails->filter(function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        })->count();

        return ($validEmails / $totalWithEmail) * 100;
    }

    protected function calculateConsistencyScore(Agency $agency): float
    {
        $total = PersonalDataSheet::where('agency_id', $agency->id)->count();
        
        if ($total === 0) return 0;

        // Check for duplicate emails
        $uniqueEmails = PersonalDataSheet::where('agency_id', $agency->id)
            ->whereNotNull('email')
            ->distinct('email')
            ->count();
        
        $totalEmails = PersonalDataSheet::where('agency_id', $agency->id)
            ->whereNotNull('email')
            ->count();

        $emailConsistency = $totalEmails > 0 ? ($uniqueEmails / $totalEmails) * 100 : 100;

        return $emailConsistency;
    }

    protected function getGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    public function generateAgencyComparison(array $agencyIds): array
    {
        $comparison = [];

        foreach ($agencyIds as $agencyId) {
            $agency = Agency::find($agencyId);
            if (!$agency) continue;

            $comparison[$agency->code] = [
                'name' => $agency->name,
                'total_pds' => PersonalDataSheet::where('agency_id', $agencyId)->count(),
                'quality_score' => $this->generateDataQualityScore($agency),
                'recent_imports' => ImportBatch::where('agency_id', $agencyId)
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
            ];
        }

        return $comparison;
    }
}
