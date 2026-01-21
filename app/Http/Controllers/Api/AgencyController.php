<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\User;
use App\Services\ReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AgencyController extends Controller
{
    protected $reconciliationService;

    public function __construct(ReconciliationService $reconciliationService)
    {
        $this->middleware('auth');
        $this->reconciliationService = $reconciliationService;
    }

    public function index()
    {
        // Super admin can see all agencies, others only their own
        if (Auth::user()->hasRole('super-admin')) {
            $agencies = Agency::with('users')->paginate(20);
        } else {
            $agencies = Agency::where('id', Auth::user()->agency_id)->with('users')->paginate(20);
        }

        return response()->json([
            'success' => true,
            'agencies' => $agencies,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Agency::class);

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:agencies,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'settings' => 'nullable|array',
            'branding' => 'nullable|array',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('agency_logos', 'public');
        }

        $agency = Agency::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Agency created successfully',
            'agency' => $agency,
        ], 201);
    }

    public function show(Agency $agency)
    {
        $this->authorize('view', $agency);

        $agency->load(['users', 'personalDataSheets']);

        $stats = [
            'total_users' => $agency->users()->count(),
            'total_pds' => $agency->personalDataSheets()->count(),
            'active_users' => $agency->users()->where('is_active', true)->count(),
            'recent_imports' => $agency->importBatches()->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'agency' => $agency,
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, Agency $agency)
    {
        $this->authorize('update', $agency);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
            'settings' => 'nullable|array',
            'branding' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($agency->logo_path) {
                Storage::disk('public')->delete($agency->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('agency_logos', 'public');
        }

        $agency->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Agency updated successfully',
            'agency' => $agency->fresh(),
        ]);
    }

    public function destroy(Agency $agency)
    {
        $this->authorize('delete', $agency);

        if ($agency->isSuperAgency()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete super agency',
            ], 403);
        }

        $agency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Agency deleted successfully',
        ]);
    }

    public function users(Agency $agency)
    {
        $this->authorize('view', $agency);

        $users = $agency->users()->with('roles')->paginate(20);

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    public function dashboard(Agency $agency)
    {
        $this->authorize('view', $agency);

        $stats = [
            'total_users' => $agency->users()->count(),
            'active_users' => $agency->users()->where('is_active', true)->count(),
            'total_pds' => $agency->personalDataSheets()->count(),
            'pending_approvals' => $agency->personalDataSheets()->where('approval_status', 'pending')->count(),
            'total_imports' => $agency->importBatches()->count(),
            'recent_imports' => $agency->importBatches()
                ->where('created_at', '>=', now()->subDays(30))
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'data_quality_score' => $this->reconciliationService->generateDataQualityScore($agency),
        ];

        return response()->json([
            'success' => true,
            'agency' => $agency,
            'dashboard' => $stats,
        ]);
    }

    public function settings(Request $request, Agency $agency)
    {
        $this->authorize('update', $agency);

        if ($request->isMethod('get')) {
            return response()->json([
                'success' => true,
                'settings' => $agency->settings,
                'branding' => $agency->branding,
            ]);
        }

        $validated = $request->validate([
            'settings' => 'nullable|array',
            'branding' => 'nullable|array',
            'branding.colors' => 'nullable|array',
            'branding.colors.primary' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'branding.colors.secondary' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if (isset($validated['settings'])) {
            $agency->settings = array_merge($agency->settings ?? [], $validated['settings']);
        }

        if (isset($validated['branding'])) {
            $agency->branding = array_merge($agency->branding ?? [], $validated['branding']);
        }

        $agency->save();

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'settings' => $agency->settings,
            'branding' => $agency->branding,
        ]);
    }
}
