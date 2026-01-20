<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Models\AuditLog;
use App\Services\DataQualityService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DataQualityService $qualityService
    ) {}

    public function index(Request $request)
    {
        $stats = [
            'total_pds' => PersonalDataSheet::count(),
            'pending_review' => PersonalDataSheet::where('status', 'pending')->count(),
            'approved' => PersonalDataSheet::where('status', 'approved')->count(),
            'draft' => PersonalDataSheet::where('status', 'draft')->count(),
        ];
        
        $qualityStats = $this->qualityService->getStatistics();
        
        $recentPDS = PersonalDataSheet::with('user', 'dataQualityScore')
            ->latest()
            ->take(10)
            ->get();
        
        $lowQualityPDS = $this->qualityService->getLowQualityPDS(60);
        
        $recentActivities = AuditLog::with('user')
            ->latest()
            ->take(15)
            ->get();
        
        $departments = PersonalDataSheet::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');
        
        return view('dashboard.index', compact(
            'stats',
            'qualityStats',
            'recentPDS',
            'lowQualityPDS',
            'recentActivities',
            'departments'
        ));
    }

    public function analytics(Request $request)
    {
        $department = $request->get('department');
        
        $filters = $department ? ['department' => $department] : [];
        $qualityStats = $this->qualityService->getStatistics($filters);
        
        $pdsPerDepartment = PersonalDataSheet::select('department')
            ->selectRaw('count(*) as total')
            ->whereNotNull('department')
            ->groupBy('department')
            ->get();
        
        $pdsPerMonth = PersonalDataSheet::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month')
            ->selectRaw('count(*) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();
        
        return view('dashboard.analytics', compact(
            'qualityStats',
            'pdsPerDepartment',
            'pdsPerMonth'
        ));
    }
}
