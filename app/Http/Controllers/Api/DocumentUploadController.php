<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PersonalDataSheet;
use App\Services\DocumentParserService;
use App\Services\ExcelImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentUploadController extends Controller
{
    public function __construct(
        protected DocumentParserService $documentParser,
        protected ExcelImportService $excelImporter
    ) {}

    public function upload(Request $request, PersonalDataSheet $personalDataSheet): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'document_type' => 'required|string|in:pds,supporting_documents',
        ]);

        $document = $this->documentParser->parseDocument(
            $personalDataSheet,
            $request->file('document'),
            $request->input('document_type')
        );

        return response()->json($document, 201);
    }

    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        // Parse Excel file
        // This is a placeholder - you would use a library like PhpSpreadsheet
        $rows = []; // Parse file to array of rows

        $results = $this->excelImporter->importFromArray($rows);

        return response()->json($results);
    }

    public function downloadTemplate(): JsonResponse
    {
        $template = $this->excelImporter->exportTemplate();

        return response()->json($template);
    }
}
