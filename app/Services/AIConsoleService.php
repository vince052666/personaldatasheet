<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\AiConsoleLog;
use App\Models\PersonalDataSheet;
use App\Models\User;
use Illuminate\Support\Str;

class AIConsoleService
{
    protected $sessionId;

    public function __construct()
    {
        $this->sessionId = Str::uuid()->toString();
    }

    public function startSession(): string
    {
        $this->sessionId = Str::uuid()->toString();
        return $this->sessionId;
    }

    public function analyzeInconsistencies(Agency $agency, User $user, array $pdsIds = []): AiConsoleLog
    {
        $prompt = $this->buildInconsistencyPrompt($agency, $pdsIds);
        
        // Simulate AI analysis (in production, this would call actual AI service)
        $response = $this->performInconsistencyAnalysis($agency, $pdsIds);
        
        return $this->logQuery(
            $agency,
            $user,
            'inconsistency_check',
            $prompt,
            $response['text'],
            ['pds_ids' => $pdsIds],
            $response['findings'],
            $response['flagged']
        );
    }

    public function validateData(Agency $agency, User $user, int $pdsId): AiConsoleLog
    {
        $pds = PersonalDataSheet::findOrFail($pdsId);
        $prompt = $this->buildValidationPrompt($pds);
        
        $response = $this->performDataValidation($pds);
        
        return $this->logQuery(
            $agency,
            $user,
            'validation',
            $prompt,
            $response['text'],
            ['pds_id' => $pdsId],
            $response['findings'],
            $response['flagged']
        );
    }

    public function suggestCorrections(Agency $agency, User $user, int $pdsId, array $fields): AiConsoleLog
    {
        $pds = PersonalDataSheet::findOrFail($pdsId);
        $prompt = $this->buildCorrectionPrompt($pds, $fields);
        
        $response = $this->performCorrectionSuggestion($pds, $fields);
        
        return $this->logQuery(
            $agency,
            $user,
            'suggestion',
            $prompt,
            $response['text'],
            ['pds_id' => $pdsId, 'fields' => $fields],
            $response['findings'],
            true // Always flag suggestions for human review
        );
    }

    public function detectDuplicates(Agency $agency, User $user): AiConsoleLog
    {
        $prompt = "Analyze all PDS records in agency {$agency->name} for potential duplicates";
        
        $response = $this->performDuplicateDetection($agency);
        
        return $this->logQuery(
            $agency,
            $user,
            'analysis',
            $prompt,
            $response['text'],
            ['agency_id' => $agency->id],
            $response['findings'],
            count($response['findings']) > 0
        );
    }

    protected function buildInconsistencyPrompt(Agency $agency, array $pdsIds): string
    {
        $scope = empty($pdsIds) ? 'all records' : count($pdsIds) . ' selected records';
        return "Check {$scope} in {$agency->name} for data inconsistencies, including:\n"
            . "- Date format inconsistencies\n"
            . "- Missing required fields\n"
            . "- Invalid email formats\n"
            . "- Age calculation errors\n"
            . "- Duplicate entries";
    }

    protected function buildValidationPrompt(PersonalDataSheet $pds): string
    {
        return "Validate PDS record for {$pds->full_name}:\n"
            . "- Check all required fields are complete\n"
            . "- Verify date formats and logical consistency\n"
            . "- Validate contact information formats\n"
            . "- Check for suspicious or unrealistic data";
    }

    protected function buildCorrectionPrompt(PersonalDataSheet $pds, array $fields): string
    {
        return "Suggest corrections for {$pds->full_name} in fields: " . implode(', ', $fields);
    }

    protected function performInconsistencyAnalysis(Agency $agency, array $pdsIds): array
    {
        $query = PersonalDataSheet::where('agency_id', $agency->id);
        
        if (!empty($pdsIds)) {
            $query->whereIn('id', $pdsIds);
        }
        
        $records = $query->get();
        $findings = [];

        // Check for missing emails
        $missingEmails = $records->where('email', null)->count();
        if ($missingEmails > 0) {
            $findings[] = [
                'type' => 'missing_data',
                'field' => 'email',
                'count' => $missingEmails,
                'severity' => 'medium',
                'message' => "{$missingEmails} records missing email address",
            ];
        }

        // Check for invalid date formats
        foreach ($records as $record) {
            if ($record->date_of_birth) {
                try {
                    $dob = new \DateTime($record->date_of_birth);
                    $age = $dob->diff(new \DateTime())->y;
                    
                    if ($age > 100 || $age < 18) {
                        $findings[] = [
                            'type' => 'data_anomaly',
                            'field' => 'date_of_birth',
                            'pds_id' => $record->id,
                            'severity' => 'high',
                            'message' => "Unusual age ({$age} years) for {$record->full_name}",
                        ];
                    }
                } catch (\Exception $e) {
                    $findings[] = [
                        'type' => 'invalid_format',
                        'field' => 'date_of_birth',
                        'pds_id' => $record->id,
                        'severity' => 'critical',
                        'message' => "Invalid date format for {$record->full_name}",
                    ];
                }
            }
        }

        $text = "Analyzed " . $records->count() . " records. Found " . count($findings) . " issues.";
        
        return [
            'text' => $text,
            'findings' => $findings,
            'flagged' => count($findings) > 0,
        ];
    }

    protected function performDataValidation(PersonalDataSheet $pds): array
    {
        $findings = [];

        // Required fields check
        $requiredFields = ['surname', 'first_name', 'date_of_birth', 'sex'];
        foreach ($requiredFields as $field) {
            if (empty($pds->$field)) {
                $findings[] = [
                    'type' => 'missing_required',
                    'field' => $field,
                    'severity' => 'high',
                    'message' => "Required field '{$field}' is missing",
                ];
            }
        }

        // Email validation
        if ($pds->email && !filter_var($pds->email, FILTER_VALIDATE_EMAIL)) {
            $findings[] = [
                'type' => 'invalid_format',
                'field' => 'email',
                'severity' => 'medium',
                'message' => "Invalid email format: {$pds->email}",
            ];
        }

        // Mobile number validation (Philippine format)
        if ($pds->mobile_no && !preg_match('/^(09|\+639)\d{9}$/', $pds->mobile_no)) {
            $findings[] = [
                'type' => 'invalid_format',
                'field' => 'mobile_no',
                'severity' => 'low',
                'message' => "Mobile number may not be in Philippine format",
            ];
        }

        $text = count($findings) > 0 
            ? "Found " . count($findings) . " validation issues" 
            : "Record validation passed";

        return [
            'text' => $text,
            'findings' => $findings,
            'flagged' => count($findings) > 0,
        ];
    }

    protected function performCorrectionSuggestion(PersonalDataSheet $pds, array $fields): array
    {
        $findings = [];

        foreach ($fields as $field) {
            $currentValue = $pds->$field;
            
            // Generate suggestions based on field type
            $suggestion = $this->generateFieldSuggestion($field, $currentValue);
            
            if ($suggestion) {
                $findings[] = [
                    'type' => 'suggestion',
                    'field' => $field,
                    'current_value' => $currentValue,
                    'suggested_value' => $suggestion,
                    'confidence' => 0.85,
                    'reason' => "AI-generated suggestion based on data patterns",
                ];
            }
        }

        return [
            'text' => "Generated " . count($findings) . " correction suggestions",
            'findings' => $findings,
            'flagged' => true, // Always flag for human review
        ];
    }

    protected function performDuplicateDetection(Agency $agency): array
    {
        $findings = [];

        // Email duplicates - use database-agnostic approach
        $emailDuplicates = PersonalDataSheet::where('agency_id', $agency->id)
            ->whereNotNull('email')
            ->select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($emailDuplicates as $dup) {
            // Get all IDs for this email using Laravel collections
            $duplicateRecords = PersonalDataSheet::where('agency_id', $agency->id)
                ->where('email', $dup->email)
                ->pluck('id');
            
            $findings[] = [
                'type' => 'duplicate',
                'field' => 'email',
                'value' => $dup->email,
                'count' => $duplicateRecords->count(),
                'pds_ids' => $duplicateRecords->toArray(),
                'severity' => 'high',
            ];
        }

        return [
            'text' => "Found " . count($findings) . " potential duplicate groups",
            'findings' => $findings,
            'flagged' => count($findings) > 0,
        ];
    }

    protected function generateFieldSuggestion(string $field, $currentValue): ?string
    {
        // Simple suggestion logic (in production, use actual AI)
        if ($field === 'email' && !empty($currentValue) && !filter_var($currentValue, FILTER_VALIDATE_EMAIL)) {
            // Try to fix common email issues
            return strtolower(trim($currentValue));
        }

        if ($field === 'mobile_no' && !empty($currentValue)) {
            // Normalize Philippine mobile numbers
            $cleaned = preg_replace('/\D/', '', $currentValue);
            if (strlen($cleaned) === 10 && substr($cleaned, 0, 1) === '9') {
                return '0' . $cleaned;
            }
        }

        return null;
    }

    protected function logQuery(
        Agency $agency,
        User $user,
        string $queryType,
        string $prompt,
        string $response,
        array $context,
        array $findings,
        bool $flagged
    ): AiConsoleLog {
        return AiConsoleLog::create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'session_id' => $this->sessionId,
            'query_type' => $queryType,
            'prompt' => $prompt,
            'response' => $response,
            'context' => $context,
            'findings' => $findings,
            'flagged_for_review' => $flagged,
        ]);
    }

    public function reviewQuery(AiConsoleLog $log, User $reviewer, string $status, ?string $notes = null, ?array $actions = null): void
    {
        $log->update([
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
            'actions_taken' => $actions,
        ]);
    }

    public function getPendingReviews(Agency $agency, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AiConsoleLog::where('agency_id', $agency->id)
            ->where('flagged_for_review', true)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getSessionHistory(string $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return AiConsoleLog::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
