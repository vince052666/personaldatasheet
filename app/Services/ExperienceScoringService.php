<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use App\Models\QualificationStandard;
use Carbon\Carbon;

class ExperienceScoringService
{
    private const RELEVANT_EXPERIENCE_MULTIPLIER = 1.5;
    private const SUPERVISORY_EXPERIENCE_MULTIPLIER = 1.3;

    public function calculateExperienceScore(PersonalDataSheet $pds, QualificationStandard $standard): float
    {
        $totalYears = $this->getTotalYearsExperience($pds);
        $relevantYears = $this->getRelevantExperienceYears($pds, $standard);
        $supervisoryYears = $this->getSupervisoryExperienceYears($pds);

        $baseScore = min($totalYears * 2, 20); // 2 points per year, max 20
        $relevantBonus = min($relevantYears * self::RELEVANT_EXPERIENCE_MULTIPLIER, 15);
        $supervisoryBonus = min($supervisoryYears * self::SUPERVISORY_EXPERIENCE_MULTIPLIER, 10);

        return min($baseScore + $relevantBonus + $supervisoryBonus, 50); // Cap at 50 points
    }

    public function getTotalYearsExperience(PersonalDataSheet $pds): float
    {
        $totalMonths = 0;

        foreach ($pds->workExperiences as $experience) {
            $from = Carbon::parse($experience->from_date);
            $to = $experience->to_date ? Carbon::parse($experience->to_date) : now();
            $totalMonths += $from->diffInMonths($to);
        }

        return round($totalMonths / 12, 2);
    }

    public function getRelevantExperienceYears(PersonalDataSheet $pds, QualificationStandard $standard): float
    {
        $totalMonths = 0;
        $positionKeywords = $this->extractKeywords($standard->position_title);

        foreach ($pds->workExperiences as $experience) {
            if ($this->isRelevantExperience($experience->position_title, $positionKeywords)) {
                $from = Carbon::parse($experience->from_date);
                $to = $experience->to_date ? Carbon::parse($experience->to_date) : now();
                $totalMonths += $from->diffInMonths($to);
            }
        }

        return round($totalMonths / 12, 2);
    }

    public function getSupervisoryExperienceYears(PersonalDataSheet $pds): float
    {
        $totalMonths = 0;
        $supervisoryKeywords = ['manager', 'supervisor', 'director', 'head', 'chief', 'lead'];

        foreach ($pds->workExperiences as $experience) {
            $positionLower = strtolower($experience->position_title);
            
            foreach ($supervisoryKeywords as $keyword) {
                if (str_contains($positionLower, $keyword)) {
                    $from = Carbon::parse($experience->from_date);
                    $to = $experience->to_date ? Carbon::parse($experience->to_date) : now();
                    $totalMonths += $from->diffInMonths($to);
                    break;
                }
            }
        }

        return round($totalMonths / 12, 2);
    }

    private function isRelevantExperience(string $experienceTitle, array $keywords): bool
    {
        $experienceLower = strtolower($experienceTitle);

        foreach ($keywords as $keyword) {
            if (str_contains($experienceLower, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function extractKeywords(string $positionTitle): array
    {
        // Remove common words and extract meaningful keywords
        $commonWords = ['and', 'or', 'the', 'a', 'an', 'of', 'in', 'to', 'for'];
        $words = explode(' ', strtolower($positionTitle));
        
        return array_filter($words, fn($word) => !in_array($word, $commonWords) && strlen($word) > 2);
    }

    public function getExperienceBreakdown(PersonalDataSheet $pds, QualificationStandard $standard): array
    {
        return [
            'total_years' => $this->getTotalYearsExperience($pds),
            'relevant_years' => $this->getRelevantExperienceYears($pds, $standard),
            'supervisory_years' => $this->getSupervisoryExperienceYears($pds),
            'positions_count' => $pds->workExperiences->count(),
        ];
    }
}
