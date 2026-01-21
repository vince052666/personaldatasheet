<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AiConsoleLog;
use App\Services\AIConsoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIConsoleController extends Controller
{
    protected $aiConsoleService;

    public function __construct(AIConsoleService $aiConsoleService)
    {
        $this->aiConsoleService = $aiConsoleService;
    }

    public function index(Request $request)
    {
        $agency = Auth::user()->agency;
        
        $logs = AiConsoleLog::where('agency_id', $agency->id)
            ->with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    public function startSession(Request $request)
    {
        $sessionId = $this->aiConsoleService->startSession();

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
        ]);
    }

    public function analyzeInconsistencies(Request $request)
    {
        $request->validate([
            'pds_ids' => 'nullable|array',
            'pds_ids.*' => 'integer|exists:personal_data_sheets,id',
        ]);

        $agency = Auth::user()->agency;
        $user = Auth::user();
        $pdsIds = $request->input('pds_ids', []);

        $log = $this->aiConsoleService->analyzeInconsistencies($agency, $user, $pdsIds);

        return response()->json([
            'success' => true,
            'log' => $log,
            'findings' => $log->findings,
        ]);
    }

    public function validateData(Request $request)
    {
        $request->validate([
            'pds_id' => 'required|integer|exists:personal_data_sheets,id',
        ]);

        $agency = Auth::user()->agency;
        $user = Auth::user();

        $log = $this->aiConsoleService->validateData($agency, $user, $request->pds_id);

        return response()->json([
            'success' => true,
            'log' => $log,
            'findings' => $log->findings,
        ]);
    }

    public function suggestCorrections(Request $request)
    {
        $request->validate([
            'pds_id' => 'required|integer|exists:personal_data_sheets,id',
            'fields' => 'required|array',
            'fields.*' => 'string',
        ]);

        $agency = Auth::user()->agency;
        $user = Auth::user();

        $log = $this->aiConsoleService->suggestCorrections(
            $agency,
            $user,
            $request->pds_id,
            $request->fields
        );

        return response()->json([
            'success' => true,
            'log' => $log,
            'suggestions' => $log->findings,
        ]);
    }

    public function detectDuplicates(Request $request)
    {
        $agency = Auth::user()->agency;
        $user = Auth::user();

        $log = $this->aiConsoleService->detectDuplicates($agency, $user);

        return response()->json([
            'success' => true,
            'log' => $log,
            'duplicates' => $log->findings,
        ]);
    }

    public function reviewQueue(Request $request)
    {
        $agency = Auth::user()->agency;
        
        $pendingReviews = $this->aiConsoleService->getPendingReviews($agency);

        return response()->json([
            'success' => true,
            'pending_reviews' => $pendingReviews,
            'count' => $pendingReviews->count(),
        ]);
    }

    public function review(Request $request, AiConsoleLog $log)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'notes' => 'nullable|string',
            'actions' => 'nullable|array',
        ]);

        $this->aiConsoleService->reviewQuery(
            $log,
            Auth::user(),
            $request->status,
            $request->notes,
            $request->actions
        );

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'log' => $log->fresh(),
        ]);
    }

    public function sessionHistory(Request $request, string $sessionId)
    {
        $history = $this->aiConsoleService->getSessionHistory($sessionId);

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'history' => $history,
        ]);
    }

    public function show(Request $request, AiConsoleLog $log)
    {
        $log->load(['user', 'reviewer']);

        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }
}
