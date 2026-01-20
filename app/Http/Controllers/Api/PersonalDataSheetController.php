<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Services\PDFGeneratorService;
use App\Services\PDSService;
use App\Services\DataQualityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PersonalDataSheetController extends Controller
{
    public function __construct(
        protected PDSService $pdsService,
        protected PDFGeneratorService $pdfGenerator,
        protected DataQualityService $qualityService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'pds_index_' . md5(json_encode($request->all()) . '_' . $request->user()->id);
        
        $pds = Cache::remember($cacheKey, 300, function () use ($request) {
            $query = QueryBuilder::for(PersonalDataSheet::class)
                ->allowedFilters([
                    AllowedFilter::exact('status'),
                    AllowedFilter::exact('department'),
                    AllowedFilter::exact('position'),
                    AllowedFilter::scope('search'),
                    AllowedFilter::exact('is_current'),
                ])
                ->allowedSorts(['created_at', 'surname', 'first_name', 'updated_at'])
                ->with([
                    'user',
                    'dataQualityScore',
                ]);

            // If user is not admin/hr, only show their own PDS
            if (!$request->user()->hasRole('admin') && !$request->user()->hasRole('hr')) {
                $query->where('user_id', $request->user()->id);
            }

            // Filter by current version
            if ($request->get('current_only')) {
                $query->where('is_current', true);
            }

            return $query->paginate($request->get('per_page', 25));
        });

        return response()->json($pds);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'citizenship' => 'required|string|max:255',
            'residential_city' => 'required|string|max:255',
            'residential_province' => 'required|string|max:255',
            'permanent_city' => 'required|string|max:255',
            'permanent_province' => 'required|string|max:255',
            'mobile_no' => 'nullable|string|max:20',
            'email_address' => 'nullable|email|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ]);

        $pds = $this->pdsService->createPDS($request->user(), $validated);
        
        Cache::tags(['pds'])->flush();

        return response()->json($pds, 201);
    }

    public function show(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $cacheKey = 'pds_' . $personalDataSheet->id;
        
        $data = Cache::remember($cacheKey, 600, function () use ($personalDataSheet) {
            return $personalDataSheet->load([
                'user',
                'workExperiences',
                'educationalBackgrounds',
                'civilServiceEligibilities',
                'trainings',
                'voluntaryWorks',
                'otherInformation',
                'versions',
                'dataQualityScore',
                'documentUploads',
            ]);
        });

        return response()->json($data);
    }

    public function update(Request $request, PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $validated = $request->validate([
            'surname' => 'sometimes|required|string|max:255',
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'change_description' => 'nullable|string',
        ]);

        $pds = $this->pdsService->updatePDS($personalDataSheet, $validated, $request->user());
        
        Cache::forget('pds_' . $personalDataSheet->id);
        Cache::tags(['pds'])->flush();

        return response()->json($pds);
    }

    public function destroy(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $this->pdsService->deletePDS($personalDataSheet);
        
        Cache::forget('pds_' . $personalDataSheet->id);
        Cache::tags(['pds'])->flush();

        return response()->json(null, 204);
    }

    public function exportPDF(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $pdfPath = $this->pdfGenerator->generatePDF($personalDataSheet);

        return response()->json([
            'message' => 'PDF generated successfully',
            'path' => $pdfPath,
        ]);
    }

    public function versions(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $versions = $personalDataSheet->versions()
            ->with('createdBy')
            ->orderBy('version_number', 'desc')
            ->paginate(10);

        return response()->json($versions);
    }

    public function qualityReport(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $report = $this->qualityService->generateQualityReport($personalDataSheet);

        return response()->json($report);
    }

    public function bulkQualityReport(Request $request): JsonResponse
    {
        $filters = $request->only(['department', 'status', 'min_score', 'max_score']);
        
        $reports = $this->qualityService->getBulkQualityReport($filters);

        return response()->json($reports);
    }

    public function statistics(Request $request): JsonResponse
    {
        $cacheKey = 'pds_statistics_' . md5(json_encode($request->all()));
        
        $stats = Cache::remember($cacheKey, 600, function () use ($request) {
            $filters = $request->only(['department']);
            return $this->qualityService->getStatistics($filters);
        });

        return response()->json($stats);
    }
}
