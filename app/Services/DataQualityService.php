<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\DataQualityScore;
use Illuminate\Support\Collection;

class DataQualityService
{
    public function __construct(
        protected AIAnalysisService $aiAnalysisService
    ) {}
    
    public function generateQualityReport(PersonalDataSheet $pds): array
    {
        $qualityScore = $pds->dataQualityScore ?? $this->aiAnalysisService->analyzeDataQuality($pds);
        
        return [
            'pds_id' => $pds->id,
            'employee_name' => $pds->full_name,
            'overall_score' => $qualityScore->overall_score,
            'scores' => [
                'completeness' => $qualityScore->completeness_score,
                'accuracy' => $qualityScore->accuracy_score,
                'consistency' => $qualityScore->consistency_score,
            ],
            'field_scores' => $qualityScore->field_scores,
            'issues' => $qualityScore->issues,
            'suggestions' => $qualityScore->suggestions,
            'status' => $this->getQualityStatus($qualityScore->overall_score),
            'analyzed_at' => $qualityScore->last_analyzed_at,
        ];
    }
    
    public function getQualityStatus(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 75) return 'good';
        if ($score >= 60) return 'fair';
        if ($score >= 40) return 'poor';
        return 'critical';
    }
    
    public function getBulkQualityReport(array $filters = []): Collection
    {
        $query = PersonalDataSheet::with('dataQualityScore');
        
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['min_score'])) {
            $query->whereHas('dataQualityScore', function ($q) use ($filters) {
                $q->where('overall_score', '>=', $filters['min_score']);
            });
        }
        
        if (!empty($filters['max_score'])) {
            $query->whereHas('dataQualityScore', function ($q) use ($filters) {
                $q->where('overall_score', '<=', $filters['max_score']);
            });
        }
        
        return $query->get()->map(function ($pds) {
            return $this->generateQualityReport($pds);
        });
    }
    
    public function getStatistics(array $filters = []): array
    {
        $query = DataQualityScore::query();
        
        if (!empty($filters['department'])) {
            $query->whereHas('personalDataSheet', function ($q) use ($filters) {
                $q->where('department', $filters['department']);
            });
        }
        
        return [
            'total_analyzed' => $query->count(),
            'average_completeness' => round($query->avg('completeness_score'), 2),
            'average_accuracy' => round($query->avg('accuracy_score'), 2),
            'average_consistency' => round($query->avg('consistency_score'), 2),
            'average_overall' => round($query->avg('overall_score'), 2),
            'excellent' => $query->where('overall_score', '>=', 90)->count(),
            'good' => $query->whereBetween('overall_score', [75, 89.99])->count(),
            'fair' => $query->whereBetween('overall_score', [60, 74.99])->count(),
            'poor' => $query->whereBetween('overall_score', [40, 59.99])->count(),
            'critical' => $query->where('overall_score', '<', 40)->count(),
        ];
    }
    
    public function getLowQualityPDS(float $threshold = 60): Collection
    {
        return PersonalDataSheet::whereHas('dataQualityScore', function ($query) use ($threshold) {
            $query->where('overall_score', '<', $threshold);
        })
        ->with('dataQualityScore')
        ->get();
    }
    
    public function getIncompletePDS(): Collection
    {
        return PersonalDataSheet::whereHas('dataQualityScore', function ($query) {
            $query->where('completeness_score', '<', 70);
        })
        ->with('dataQualityScore')
        ->get();
    }
    
    public function getMissingRequiredFields(PersonalDataSheet $pds): array
    {
        $required = [
            'surname' => 'Surname',
            'first_name' => 'First Name',
            'date_of_birth' => 'Date of Birth',
            'place_of_birth' => 'Place of Birth',
            'sex' => 'Sex',
            'civil_status' => 'Civil Status',
            'citizenship' => 'Citizenship',
            'mobile_no' => 'Mobile Number',
            'email_address' => 'Email Address',
            'residential_city' => 'Residential City',
            'residential_province' => 'Residential Province',
        ];
        
        $missing = [];
        foreach ($required as $field => $label) {
            if (empty($pds->$field)) {
                $missing[] = $label;
            }
        }
        
        return $missing;
    }
}
