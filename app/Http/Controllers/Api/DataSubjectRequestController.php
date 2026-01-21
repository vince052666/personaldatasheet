<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataSubjectRequest;
use App\Models\PersonalDataSheet;
use App\Services\LoggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DataSubjectRequestController extends Controller
{
    protected LoggingService $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    public function index()
    {
        $this->authorize('manage-dpa-requests');

        $requests = DataSubjectRequest::with(['agency', 'personalDataSheet', 'processedBy'])
            ->where('agency_id', auth()->user()->agency_id)
            ->latest()
            ->paginate(20);

        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|in:access,rectification,erasure,portability',
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'required|email',
            'requester_id_number' => 'nullable|string',
            'personal_data_sheet_id' => 'nullable|exists:personal_data_sheets,id',
            'request_details' => 'required|string',
        ]);

        $validated['agency_id'] = auth()->user()->agency_id;
        $validated['submitted_at'] = now();

        $dsr = DataSubjectRequest::create($validated);

        $this->logger->info("Data subject request created", [
            'dsr_id' => $dsr->id,
            'type' => $dsr->request_type,
        ]);

        return response()->json($dsr, 201);
    }

    public function show(DataSubjectRequest $dataSubjectRequest)
    {
        $this->authorize('view', $dataSubjectRequest);

        return response()->json($dataSubjectRequest->load(['agency', 'personalDataSheet', 'processedBy']));
    }

    public function process(Request $request, DataSubjectRequest $dataSubjectRequest)
    {
        $this->authorize('manage-dpa-requests');

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
        ]);

        $dataSubjectRequest->markAsProcessing(auth()->user());

        if ($validated['action'] === 'approve') {
            $response = $this->processRequest($dataSubjectRequest);
            $dataSubjectRequest->markAsCompleted($validated['notes'] ?? 'Request processed successfully');
        } else {
            $dataSubjectRequest->markAsRejected($validated['notes'] ?? 'Request rejected');
            $response = null;
        }

        $this->logger->info("Data subject request processed", [
            'dsr_id' => $dataSubjectRequest->id,
            'action' => $validated['action'],
        ]);

        return response()->json([
            'message' => 'Request processed successfully',
            'request' => $dataSubjectRequest,
            'response' => $response,
        ]);
    }

    protected function processRequest(DataSubjectRequest $dsr): ?array
    {
        switch ($dsr->request_type) {
            case 'access':
                return $this->processAccessRequest($dsr);
            case 'portability':
                return $this->processPortabilityRequest($dsr);
            case 'rectification':
                return $this->processRectificationRequest($dsr);
            case 'erasure':
                return $this->processErasureRequest($dsr);
            default:
                return null;
        }
    }

    protected function processAccessRequest(DataSubjectRequest $dsr): array
    {
        if (!$dsr->personal_data_sheet_id) {
            return ['message' => 'No PDS record found'];
        }

        $pds = PersonalDataSheet::with([
            'educationalBackground',
            'workExperience',
            'civilServiceEligibility',
            'trainings',
            'voluntaryWork',
            'otherInformation',
        ])->find($dsr->personal_data_sheet_id);

        return [
            'message' => 'Data access granted',
            'data' => $pds->toArray(),
        ];
    }

    protected function processPortabilityRequest(DataSubjectRequest $dsr): array
    {
        if (!$dsr->personal_data_sheet_id) {
            return ['message' => 'No PDS record found'];
        }

        $pds = PersonalDataSheet::with([
            'educationalBackground',
            'workExperience',
            'civilServiceEligibility',
            'trainings',
            'voluntaryWork',
            'otherInformation',
        ])->find($dsr->personal_data_sheet_id);

        $filename = "pds_export_{$dsr->id}_" . now()->format('YmdHis') . '.json';
        $content = json_encode($pds->toArray(), JSON_PRETTY_PRINT);

        Storage::disk('local')->put("dpa_exports/{$filename}", $content);

        return [
            'message' => 'Data export created',
            'filename' => $filename,
            'download_url' => route('dpa.download', ['filename' => $filename]),
        ];
    }

    protected function processRectificationRequest(DataSubjectRequest $dsr): array
    {
        return [
            'message' => 'Rectification request noted. Please update the record directly.',
            'pds_id' => $dsr->personal_data_sheet_id,
        ];
    }

    protected function processErasureRequest(DataSubjectRequest $dsr): array
    {
        if (!$dsr->personal_data_sheet_id) {
            return ['message' => 'No PDS record found'];
        }

        $pds = PersonalDataSheet::find($dsr->personal_data_sheet_id);

        $pds->delete();

        $this->logger->auditLog('erasure', PersonalDataSheet::class, $pds->id, [
            'reason' => 'Data subject erasure request',
            'dsr_id' => $dsr->id,
        ]);

        return [
            'message' => 'Data erased successfully',
            'pds_id' => $pds->id,
        ];
    }
}
