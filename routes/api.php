<?php

use App\Http\Controllers\Api\AgencyController;
use App\Http\Controllers\Api\AIConsoleController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DocumentUploadController;
use App\Http\Controllers\Api\PersonalDataSheetController;
use App\Http\Controllers\Api\HealthCheckController;
use Illuminate\Support\Facades\Route;

// Health Check Endpoints (no authentication required)
Route::prefix('health')->group(function () {
    Route::get('/', [HealthCheckController::class, 'index']);
    Route::get('/database', [HealthCheckController::class, 'database']);
    Route::get('/cache', [HealthCheckController::class, 'cache']);
    Route::get('/queue', [HealthCheckController::class, 'queue']);
    Route::get('/storage', [HealthCheckController::class, 'storage']);
});

// API v1 Routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    
    // Personal Data Sheet Routes
    Route::apiResource('personal-data-sheets', PersonalDataSheetController::class);
    Route::get('personal-data-sheets/{personalDataSheet}/versions', [PersonalDataSheetController::class, 'versions']);
    Route::post('personal-data-sheets/{personalDataSheet}/export-pdf', [PersonalDataSheetController::class, 'exportPDF']);
    Route::get('personal-data-sheets/{personalDataSheet}/quality-report', [PersonalDataSheetController::class, 'qualityReport']);
    Route::get('personal-data-sheets/reports/bulk-quality', [PersonalDataSheetController::class, 'bulkQualityReport']);
    Route::get('personal-data-sheets/reports/statistics', [PersonalDataSheetController::class, 'statistics']);
    
    // Document Upload Routes
    Route::post('personal-data-sheets/{personalDataSheet}/documents', [DocumentUploadController::class, 'upload']);
    Route::post('documents/import-excel', [DocumentUploadController::class, 'importExcel'])
        ->middleware('can:import-pds');
    Route::get('documents/import-template', [DocumentUploadController::class, 'downloadTemplate']);
    
    // Audit Log Routes
    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('can:view-audit-logs');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])
        ->middleware('can:view-audit-logs');
    
    // Approval Workflow Routes
    Route::prefix('approvals')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ApprovalController::class, 'index']);
        Route::post('/{pds}/submit', [App\Http\Controllers\Api\ApprovalController::class, 'submit']);
        Route::post('/{workflow}/approve', [App\Http\Controllers\Api\ApprovalController::class, 'approve']);
        Route::post('/{workflow}/reject', [App\Http\Controllers\Api\ApprovalController::class, 'reject']);
        Route::post('/{workflow}/reassign', [App\Http\Controllers\Api\ApprovalController::class, 'reassign']);
    });
    
    // Recruitment Routes
    Route::prefix('recruitment')->group(function () {
        Route::get('/qualification-standards', [App\Http\Controllers\Api\RecruitmentController::class, 'qualificationStandards']);
        Route::post('/rank/{standard}', [App\Http\Controllers\Api\RecruitmentController::class, 'rankCandidates']);
        Route::get('/rankings/{standard}', [App\Http\Controllers\Api\RecruitmentController::class, 'rankings']);
        Route::post('/assess-readiness/{pds}/{standard}', [App\Http\Controllers\Api\RecruitmentController::class, 'assessReadiness']);
    });
    
    // Data Subject Requests (Privacy Compliance)
    Route::prefix('privacy')->group(function () {
        Route::post('/consent', [App\Http\Controllers\Api\PrivacyController::class, 'giveConsent']);
        Route::delete('/consent/{type}', [App\Http\Controllers\Api\PrivacyController::class, 'withdrawConsent']);
        Route::post('/data-request', [App\Http\Controllers\Api\PrivacyController::class, 'submitRequest']);
        Route::get('/my-data', [App\Http\Controllers\Api\PrivacyController::class, 'downloadMyData']);
    });
    
    // Agency Management Routes
    Route::prefix('agencies')->group(function () {
        Route::get('/', [AgencyController::class, 'index']);
        Route::post('/', [AgencyController::class, 'store'])->middleware('can:create-agency');
        Route::get('/{agency}', [AgencyController::class, 'show']);
        Route::put('/{agency}', [AgencyController::class, 'update']);
        Route::delete('/{agency}', [AgencyController::class, 'destroy'])->middleware('can:delete-agency');
        Route::get('/{agency}/users', [AgencyController::class, 'users']);
        Route::get('/{agency}/dashboard', [AgencyController::class, 'dashboard']);
        Route::match(['get', 'post'], '/{agency}/settings', [AgencyController::class, 'settings']);
    });
    
    // AI Console Routes
    Route::prefix('ai-console')->group(function () {
        Route::get('/', [AIConsoleController::class, 'index']);
        Route::post('/session/start', [AIConsoleController::class, 'startSession']);
        Route::post('/analyze-inconsistencies', [AIConsoleController::class, 'analyzeInconsistencies']);
        Route::post('/validate-data', [AIConsoleController::class, 'validateData']);
        Route::post('/suggest-corrections', [AIConsoleController::class, 'suggestCorrections']);
        Route::post('/detect-duplicates', [AIConsoleController::class, 'detectDuplicates']);
        Route::get('/review-queue', [AIConsoleController::class, 'reviewQueue']);
        Route::post('/logs/{log}/review', [AIConsoleController::class, 'review']);
        Route::get('/logs/{log}', [AIConsoleController::class, 'show']);
        Route::get('/session/{sessionId}/history', [AIConsoleController::class, 'sessionHistory']);
    });
});
