<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RankingService;
use App\Services\AppointmentReadinessService;
use App\Models\QualificationStandard;
use App\Models\PersonalDataSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruitmentController extends Controller
{
    private RankingService $rankingService;
    private AppointmentReadinessService $readinessService;

    public function __construct(
        RankingService $rankingService,
        AppointmentReadinessService $readinessService
    ) {
        $this->rankingService = $rankingService;
        $this->readinessService = $readinessService;
    }

    public function qualificationStandards(Request $request): JsonResponse
    {
        $standards = QualificationStandard::query()
            ->when($request->query('search'), function ($query, $search) {
                $query->where('position_title', 'like', "%{$search}%");
            })
            ->paginate(20);

        return response()->json($standards);
    }

    public function rankCandidates(int $standardId): JsonResponse
    {
        $standard = QualificationStandard::findOrFail($standardId);

        $rankings = $this->rankingService->rankCandidates($standard);

        return response()->json([
            'message' => 'Candidates ranked successfully',
            'data' => [
                'qualification_standard' => $standard,
                'rankings' => $rankings,
                'total_candidates' => count($rankings),
                'qualified_candidates' => collect($rankings)->where('meets_requirements', true)->count(),
            ],
        ]);
    }

    public function rankings(int $standardId): JsonResponse
    {
        $standard = QualificationStandard::findOrFail($standardId);
        $report = $this->rankingService->generateRankingReport($standard);

        return response()->json([
            'data' => $report,
        ]);
    }

    public function assessReadiness(int $pdsId, int $standardId): JsonResponse
    {
        $pds = PersonalDataSheet::findOrFail($pdsId);
        $standard = QualificationStandard::findOrFail($standardId);

        $readiness = $this->readinessService->assessReadiness($pds, $standard);

        return response()->json([
            'data' => $readiness,
            'summary' => [
                'is_ready' => $readiness->is_ready,
                'missing_count' => count($readiness->missing_requirements ?? []),
            ],
        ]);
    }
}
