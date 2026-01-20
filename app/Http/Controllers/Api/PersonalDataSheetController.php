<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Services\PDFGeneratorService;
use App\Services\PDSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalDataSheetController extends Controller
{
    public function __construct(
        protected PDSService $pdsService,
        protected PDFGeneratorService $pdfGenerator
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = PersonalDataSheet::with([
            'user',
            'workExperiences',
            'educationalBackgrounds',
            'civilServiceEligibilities',
            'trainings',
            'voluntaryWorks',
            'otherInformation',
        ]);

        // If user is not admin/hr, only show their own PDS
        if (!$request->user()->hasRole('admin') && !$request->user()->hasRole('hr')) {
            $query->where('user_id', $request->user()->id);
        }

        // Filter by current version
        if ($request->get('current_only')) {
            $query->where('is_current', true);
        }

        $pds = $query->paginate($request->get('per_page', 15));

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
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Other',
            'citizenship' => 'required|string|max:255',
            'residential_city' => 'required|string|max:255',
            'residential_province' => 'required|string|max:255',
            'permanent_city' => 'required|string|max:255',
            'permanent_province' => 'required|string|max:255',
        ]);

        $pds = $this->pdsService->createPDS($request->user(), $validated);

        return response()->json($pds, 201);
    }

    public function show(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $personalDataSheet->load([
            'user',
            'workExperiences',
            'educationalBackgrounds',
            'civilServiceEligibilities',
            'trainings',
            'voluntaryWorks',
            'otherInformation',
            'versions',
        ]);

        return response()->json($personalDataSheet);
    }

    public function update(Request $request, PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $validated = $request->validate([
            'surname' => 'sometimes|required|string|max:255',
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'change_description' => 'nullable|string',
        ]);

        $pds = $this->pdsService->updatePDS($personalDataSheet, $validated);

        return response()->json($pds);
    }

    public function destroy(PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $this->pdsService->deletePDS($personalDataSheet);

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
            ->get();

        return response()->json($versions);
    }
}
