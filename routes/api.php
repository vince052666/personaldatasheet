<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\DocumentUploadController;
use App\Http\Controllers\Api\PersonalDataSheetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Personal Data Sheet Routes
    Route::apiResource('personal-data-sheets', PersonalDataSheetController::class);
    Route::get('personal-data-sheets/{personalDataSheet}/versions', [PersonalDataSheetController::class, 'versions']);
    Route::post('personal-data-sheets/{personalDataSheet}/export-pdf', [PersonalDataSheetController::class, 'exportPDF']);
    
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
});
