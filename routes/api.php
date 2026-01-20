<?php

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
        Route::get('/', 'ApprovalController@index');
        Route::post('/{pds}/submit', 'ApprovalController@submit');
        Route::post('/{workflow}/approve', 'ApprovalController@approve');
        Route::post('/{workflow}/reject', 'ApprovalController@reject');
        Route::post('/{workflow}/reassign', 'ApprovalController@reassign');
    });
    
    // Recruitment Routes
    Route::prefix('recruitment')->group(function () {
        Route::get('/qualification-standards', 'RecruitmentController@qualificationStandards');
        Route::post('/rank/{standard}', 'RecruitmentController@rankCandidates');
        Route::get('/rankings/{standard}', 'RecruitmentController@rankings');
        Route::post('/assess-readiness/{pds}/{standard}', 'RecruitmentController@assessReadiness');
    });
    
    // Data Subject Requests (Privacy Compliance)
    Route::prefix('privacy')->group(function () {
        Route::post('/consent', 'PrivacyController@giveConsent');
        Route::delete('/consent/{type}', 'PrivacyController@withdrawConsent');
        Route::post('/data-request', 'PrivacyController@submitRequest');
        Route::get('/my-data', 'PrivacyController@downloadMyData');
    });
});
