<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Services\PDSService;
use App\Services\AIAnalysisService;
use App\Jobs\AnalyzeDataQuality;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class PDSController extends Controller
{
    public function __construct(
        protected PDSService $pdsService,
        protected AIAnalysisService $aiAnalysisService
    ) {}

    public function index(Request $request)
    {
        $pds = QueryBuilder::for(PersonalDataSheet::class)
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('department'),
                AllowedFilter::exact('position'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts(['created_at', 'surname', 'first_name'])
            ->with(['user', 'dataQualityScore'])
            ->paginate(25)
            ->withQueryString();

        $departments = PersonalDataSheet::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return view('pds.index', compact('pds', 'departments'));
    }

    public function create()
    {
        return view('pds.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->validationRules());
        
        $pds = $this->pdsService->createPDS($validated, $request->user());
        
        AnalyzeDataQuality::dispatch($pds);
        
        return redirect()->route('pds.show', $pds)
            ->with('success', 'PDS created successfully');
    }

    public function show(PersonalDataSheet $pd)
    {
        $pd->load([
            'user',
            'workExperiences',
            'educationalBackgrounds',
            'civilServiceEligibilities',
            'trainings',
            'voluntaryWorks',
            'otherInformation',
            'dataQualityScore',
            'documentUploads'
        ]);

        return view('pds.show', compact('pd'));
    }

    public function edit(PersonalDataSheet $pd)
    {
        return view('pds.edit', compact('pd'));
    }

    public function update(Request $request, PersonalDataSheet $pd)
    {
        $validated = $request->validate($this->validationRules());
        
        $pd = $this->pdsService->updatePDS($pd, $validated, $request->user());
        
        AnalyzeDataQuality::dispatch($pd);
        
        return redirect()->route('pds.show', $pd)
            ->with('success', 'PDS updated successfully');
    }

    public function destroy(PersonalDataSheet $pd)
    {
        $pd->delete();
        
        return redirect()->route('pds.index')
            ->with('success', 'PDS deleted successfully');
    }

    protected function validationRules(): array
    {
        return [
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'name_extension' => 'nullable|string|max:10',
            'date_of_birth' => 'required|date',
            'place_of_birth' => 'required|string|max:255',
            'sex' => 'required|in:Male,Female',
            'civil_status' => 'required|in:Single,Married,Widowed,Separated,Divorced',
            'height' => 'nullable|numeric|min:100|max:250',
            'weight' => 'nullable|numeric|min:30|max:200',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'gsis_id_no' => 'nullable|string|max:50',
            'pagibig_id_no' => 'nullable|string|max:50',
            'philhealth_no' => 'nullable|string|max:50',
            'sss_no' => 'nullable|string|max:50',
            'tin_no' => 'nullable|string|max:50',
            'citizenship' => 'required|string|max:50',
            'mobile_no' => 'required|string|max:20',
            'email_address' => 'required|email|max:255',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'height' => 'nullable|numeric|min:1.0|max:2.5',
            'weight' => 'nullable|numeric|min:30|max:200',
        ];
    }
}
