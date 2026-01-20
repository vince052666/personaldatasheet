<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\DataQualityScore;
use Illuminate\Support\Carbon;

class AIAnalysisService
{
    public function analyzeDataQuality(PersonalDataSheet $pds): DataQualityScore
    {
        $completeness = $this->calculateCompletenessScore($pds);
        $accuracy = $this->calculateAccuracyScore($pds);
        $consistency = $this->calculateConsistencyScore($pds);
        
        $overall = ($completeness + $accuracy + $consistency) / 3;
        
        $qualityScore = $pds->dataQualityScore()->updateOrCreate(
            ['personal_data_sheet_id' => $pds->id],
            [
                'completeness_score' => $completeness,
                'accuracy_score' => $accuracy,
                'consistency_score' => $consistency,
                'overall_score' => $overall,
                'field_scores' => $this->getFieldScores($pds),
                'issues' => $this->detectIssues($pds),
                'suggestions' => $this->generateSuggestions($pds),
                'last_analyzed_at' => now(),
            ]
        );
        
        return $qualityScore;
    }
    
    protected function calculateCompletenessScore(PersonalDataSheet $pds): float
    {
        $requiredFields = [
            'surname', 'first_name', 'date_of_birth', 'place_of_birth',
            'sex', 'civil_status', 'citizenship', 'mobile_no', 'email_address',
            'residential_city', 'residential_province', 'permanent_city', 'permanent_province',
        ];
        
        $filledCount = 0;
        foreach ($requiredFields as $field) {
            if (!empty($pds->$field)) {
                $filledCount++;
            }
        }
        
        $fieldScore = ($filledCount / count($requiredFields)) * 50;
        
        $relationScore = 0;
        if ($pds->educationalBackgrounds()->count() > 0) $relationScore += 15;
        if ($pds->workExperiences()->count() > 0) $relationScore += 15;
        if ($pds->civilServiceEligibilities()->count() > 0) $relationScore += 10;
        if ($pds->trainings()->count() > 0) $relationScore += 10;
        
        return min(100, $fieldScore + $relationScore);
    }
    
    protected function calculateAccuracyScore(PersonalDataSheet $pds): float
    {
        $score = 100;
        $issues = [];
        
        if ($pds->date_of_birth && $pds->date_of_birth->isFuture()) {
            $score -= 20;
            $issues[] = 'Future birth date';
        }
        
        if ($pds->date_of_birth && $pds->date_of_birth->age < 18) {
            $score -= 15;
            $issues[] = 'Age below 18';
        }
        
        if ($pds->email_address && !filter_var($pds->email_address, FILTER_VALIDATE_EMAIL)) {
            $score -= 10;
            $issues[] = 'Invalid email format';
        }
        
        if ($pds->mobile_no && !preg_match('/^(09|\+639)\d{9}$/', $pds->mobile_no)) {
            $score -= 10;
            $issues[] = 'Invalid mobile number format';
        }
        
        if ($pds->height && ($pds->height < 100 || $pds->height > 250)) {
            $score -= 10;
            $issues[] = 'Height out of normal range';
        }
        
        if ($pds->weight && ($pds->weight < 30 || $pds->weight > 200)) {
            $score -= 10;
            $issues[] = 'Weight out of normal range';
        }
        
        return max(0, $score);
    }
    
    protected function calculateConsistencyScore(PersonalDataSheet $pds): float
    {
        $score = 100;
        
        $residentialComplete = $pds->residential_city && $pds->residential_province;
        $permanentComplete = $pds->permanent_city && $pds->permanent_province;
        
        if (!$residentialComplete || !$permanentComplete) {
            $score -= 20;
        }
        
        if ($pds->civil_status === 'Married' && !$pds->spouse_surname && !$pds->spouse_first_name) {
            $score -= 15;
        }
        
        foreach ($pds->workExperiences as $work) {
            if ($work->date_from && $work->date_to && $work->date_from > $work->date_to) {
                $score -= 10;
                break;
            }
        }
        
        foreach ($pds->educationalBackgrounds as $edu) {
            if ($edu->year_from && $edu->year_to && $edu->year_from > $edu->year_to) {
                $score -= 10;
                break;
            }
        }
        
        return max(0, $score);
    }
    
    protected function getFieldScores(PersonalDataSheet $pds): array
    {
        return [
            'personal_info' => $this->scoreSection(['surname', 'first_name', 'date_of_birth', 'sex'], $pds),
            'contact_info' => $this->scoreSection(['mobile_no', 'email_address', 'telephone_no'], $pds),
            'address_info' => $this->scoreSection(['residential_city', 'residential_province', 'permanent_city', 'permanent_province'], $pds),
            'family_info' => $this->scoreSection(['father_surname', 'mother_surname'], $pds),
            'education' => min(100, $pds->educationalBackgrounds()->count() * 25),
            'work_experience' => min(100, $pds->workExperiences()->count() * 20),
        ];
    }
    
    protected function scoreSection(array $fields, PersonalDataSheet $pds): float
    {
        $filledCount = 0;
        foreach ($fields as $field) {
            if (!empty($pds->$field)) {
                $filledCount++;
            }
        }
        return count($fields) > 0 ? ($filledCount / count($fields)) * 100 : 0;
    }
    
    protected function detectIssues(PersonalDataSheet $pds): array
    {
        $issues = [];
        
        if (!$pds->email_address) {
            $issues[] = ['field' => 'email_address', 'type' => 'missing', 'severity' => 'high'];
        }
        
        if (!$pds->mobile_no) {
            $issues[] = ['field' => 'mobile_no', 'type' => 'missing', 'severity' => 'high'];
        }
        
        if ($pds->educationalBackgrounds()->count() === 0) {
            $issues[] = ['field' => 'educational_backgrounds', 'type' => 'missing', 'severity' => 'high'];
        }
        
        if ($pds->workExperiences()->count() === 0 && $pds->date_of_birth && $pds->date_of_birth->age > 22) {
            $issues[] = ['field' => 'work_experiences', 'type' => 'missing', 'severity' => 'medium'];
        }
        
        if ($pds->civil_status === 'Married' && !$pds->spouse_surname) {
            $issues[] = ['field' => 'spouse_info', 'type' => 'incomplete', 'severity' => 'medium'];
        }
        
        return $issues;
    }
    
    protected function generateSuggestions(PersonalDataSheet $pds): array
    {
        $suggestions = [];
        
        if (!$pds->email_address) {
            $suggestions[] = 'Add email address for better communication';
        }
        
        if (!$pds->mobile_no) {
            $suggestions[] = 'Add mobile number for contact purposes';
        }
        
        if ($pds->educationalBackgrounds()->count() === 0) {
            $suggestions[] = 'Add educational background information';
        }
        
        if ($pds->workExperiences()->count() === 0) {
            $suggestions[] = 'Add work experience information if applicable';
        }
        
        if (!$pds->civilServiceEligibilities()->count()) {
            $suggestions[] = 'Add civil service eligibility if applicable';
        }
        
        if (!$pds->trainings()->count()) {
            $suggestions[] = 'Add training/seminars attended';
        }
        
        if ($pds->civil_status === 'Married' && !$pds->spouse_surname) {
            $suggestions[] = 'Complete spouse information';
        }
        
        if (empty($pds->children) && $pds->civil_status === 'Married') {
            $suggestions[] = 'Add children information if applicable';
        }
        
        return $suggestions;
    }
    
    public function getConfidenceScore(array $parsedData, string $fieldName): float
    {
        $value = $parsedData[$fieldName] ?? null;
        
        if (empty($value)) {
            return 0;
        }
        
        $confidence = 50;
        
        switch ($fieldName) {
            case 'email_address':
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $confidence = 95;
                }
                break;
                
            case 'mobile_no':
                if (preg_match('/^(09|\+639)\d{9}$/', $value)) {
                    $confidence = 90;
                }
                break;
                
            case 'date_of_birth':
                try {
                    $date = Carbon::parse($value);
                    if (!$date->isFuture() && $date->age >= 18 && $date->age <= 100) {
                        $confidence = 85;
                    }
                } catch (\Exception $e) {
                    $confidence = 20;
                }
                break;
                
            case 'surname':
            case 'first_name':
                if (preg_match('/^[A-Za-z\s\-\.]+$/', $value) && strlen($value) >= 2) {
                    $confidence = 80;
                }
                break;
                
            default:
                $confidence = 70;
        }
        
        return $confidence;
    }
    
    public function detectAnomalies(PersonalDataSheet $pds): array
    {
        $anomalies = [];
        
        if ($pds->workExperiences()->count() > 20) {
            $anomalies[] = [
                'type' => 'excessive_work_experience',
                'message' => 'Unusually high number of work experiences',
                'severity' => 'low'
            ];
        }
        
        $overlappingJobs = $this->detectOverlappingWorkExperiences($pds);
        if (count($overlappingJobs) > 0) {
            $anomalies[] = [
                'type' => 'overlapping_work_experience',
                'message' => 'Overlapping work experience dates detected',
                'severity' => 'medium',
                'details' => $overlappingJobs
            ];
        }
        
        if ($pds->date_of_birth && $pds->date_of_birth->age > 70) {
            $anomalies[] = [
                'type' => 'age_concern',
                'message' => 'Age exceeds typical working age',
                'severity' => 'low'
            ];
        }
        
        return $anomalies;
    }
    
    protected function detectOverlappingWorkExperiences(PersonalDataSheet $pds): array
    {
        $experiences = $pds->workExperiences()->orderBy('date_from')->get();
        $overlaps = [];
        
        for ($i = 0; $i < count($experiences) - 1; $i++) {
            for ($j = $i + 1; $j < count($experiences); $j++) {
                $exp1 = $experiences[$i];
                $exp2 = $experiences[$j];
                
                if ($exp1->date_to && $exp2->date_from && 
                    $exp1->date_to >= $exp2->date_from && 
                    $exp1->date_from <= $exp2->date_to) {
                    $overlaps[] = [
                        'job1' => $exp1->position_title,
                        'job2' => $exp2->position_title,
                        'period' => $exp2->date_from->format('Y-m-d') . ' to ' . $exp1->date_to->format('Y-m-d')
                    ];
                }
            }
        }
        
        return $overlaps;
    }
}
