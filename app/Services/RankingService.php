<?php

namespace App\Services;

use App\Models\QualificationStandard;
use App\Models\PersonalDataSheet;
use App\Models\CandidateRanking;
use Illuminate\Support\Facades\DB;

class RankingService
{
    private ExperienceScoringService $experienceService;

    public function __construct(ExperienceScoringService $experienceService)
    {
        $this->experienceService = $experienceService;
    }

    public function rankCandidates(QualificationStandard $standard): array
    {
        $candidates = PersonalDataSheet::with([
            'educationalBackgrounds',
            'workExperiences',
            'trainings',
            'civilServiceEligibilities'
        ])->get();

        $rankings = [];

        foreach ($candidates as $pds) {
            $ranking = $this->calculateRanking($pds, $standard);
            $rankings[] = $ranking;
        }

        // Sort by total score descending
        usort($rankings, fn($a, $b) => $b->total_score <=> $a->total_score);

        // Assign ranks
        foreach ($rankings as $index => $ranking) {
            $ranking->rank = $index + 1;
            $ranking->save();
        }

        return $rankings;
    }

    public function calculateRanking(PersonalDataSheet $pds, QualificationStandard $standard): CandidateRanking
    {
        $educationScore = $this->scoreEducation($pds, $standard);
        $experienceScore = $this->experienceService->calculateExperienceScore($pds, $standard);
        $trainingScore = $this->scoreTraining($pds, $standard);
        $eligibilityScore = $this->scoreEligibility($pds, $standard);

        $totalScore = $educationScore + $experienceScore + $trainingScore + $eligibilityScore;
        $meetsRequirements = $this->checkMeetsRequirements($pds, $standard);

        return CandidateRanking::updateOrCreate(
            [
                'personal_data_sheet_id' => $pds->id,
                'qualification_standard_id' => $standard->id,
            ],
            [
                'education_score' => $educationScore,
                'experience_score' => $experienceScore,
                'training_score' => $trainingScore,
                'eligibility_score' => $eligibilityScore,
                'total_score' => $totalScore,
                'meets_requirements' => $meetsRequirements,
                'score_breakdown' => [
                    'education' => $educationScore,
                    'experience' => $experienceScore,
                    'training' => $trainingScore,
                    'eligibility' => $eligibilityScore,
                ],
                'calculated_at' => now(),
            ]
        );
    }

    private function scoreEducation(PersonalDataSheet $pds, QualificationStandard $standard): float
    {
        $score = 0;
        $highestLevel = $this->getHighestEducationLevel($pds);

        $requiredLevel = $this->parseEducationRequirement($standard->education_requirement);

        if ($highestLevel >= $requiredLevel) {
            $score = 25; // Base score for meeting requirement
            
            // Bonus for exceeding
            if ($highestLevel > $requiredLevel) {
                $score += ($highestLevel - $requiredLevel) * 5;
            }
        }

        return min($score, 30); // Cap at 30 points
    }

    private function scoreTraining(PersonalDataSheet $pds, QualificationStandard $standard): float
    {
        $totalHours = $pds->trainings->sum('number_of_hours');
        $relevantTrainings = $pds->trainings->count();

        $score = min($relevantTrainings * 2, 20); // 2 points per training, max 20

        return $score;
    }

    private function scoreEligibility(PersonalDataSheet $pds, QualificationStandard $standard): float
    {
        $eligibilities = $pds->civilServiceEligibilities;
        
        if ($eligibilities->isEmpty()) {
            return 0;
        }

        $score = 20; // Base score for having eligibility
        
        // Check for professional level
        if ($eligibilities->where('eligibility_type', 'Professional')->count() > 0) {
            $score = 25;
        }

        return $score;
    }

    private function checkMeetsRequirements(PersonalDataSheet $pds, QualificationStandard $standard): bool
    {
        $meetsEducation = $this->getHighestEducationLevel($pds) >= $this->parseEducationRequirement($standard->education_requirement);
        $meetsExperience = $this->experienceService->getTotalYearsExperience($pds) >= $standard->minimum_years_experience;
        $meetsEligibility = $pds->civilServiceEligibilities->isNotEmpty();

        return $meetsEducation && $meetsExperience && $meetsEligibility;
    }

    private function getHighestEducationLevel(PersonalDataSheet $pds): int
    {
        $levelMap = [
            'Elementary' => 1,
            'Secondary' => 2,
            'Vocational' => 3,
            'College' => 4,
            'Graduate Studies' => 5,
        ];

        $highestLevel = 0;

        foreach ($pds->educationalBackgrounds as $education) {
            $level = $levelMap[$education->level] ?? 0;
            if ($level > $highestLevel) {
                $highestLevel = $level;
            }
        }

        return $highestLevel;
    }

    private function parseEducationRequirement(?string $requirement): int
    {
        if (!$requirement) return 4; // Default to college

        $requirement = strtolower($requirement);
        
        if (str_contains($requirement, 'graduate') || str_contains($requirement, 'master')) {
            return 5;
        } elseif (str_contains($requirement, 'college') || str_contains($requirement, 'bachelor')) {
            return 4;
        } elseif (str_contains($requirement, 'vocational')) {
            return 3;
        } elseif (str_contains($requirement, 'secondary') || str_contains($requirement, 'high school')) {
            return 2;
        }

        return 4; // Default
    }

    public function generateRankingReport(QualificationStandard $standard)
    {
        return CandidateRanking::with('personalDataSheet')
            ->where('qualification_standard_id', $standard->id)
            ->orderBy('rank')
            ->get();
    }
}
