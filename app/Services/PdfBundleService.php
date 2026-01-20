<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PdfBundleService
{
    protected $pdfGenerator;

    public function __construct(PDFGeneratorService $pdfGenerator)
    {
        $this->pdfGenerator = $pdfGenerator;
    }

    public function generateCompletePackage(PersonalDataSheet $pds): string
    {
        $tempDir = 'temp/bundle_' . $pds->id . '_' . time();
        Storage::makeDirectory($tempDir);

        try {
            // 1. Generate main CSC Form 212 (PDS)
            $pdsPath = $this->generateEnhancedPDS($pds, $tempDir);

            // 2. Generate Work Experience Sheet (if applicable)
            $wesPath = $this->generateWorkExperienceSheet($pds, $tempDir);

            // 3. Collect all attachments
            $attachmentPaths = $this->collectAttachments($pds, $tempDir);

            // 4. Generate certification hash document
            $certPath = $this->generateCertificationDocument($pds, $tempDir);

            // 5. Create ZIP bundle
            $zipPath = $this->createZipBundle($pds, $tempDir, array_filter([
                $pdsPath,
                $wesPath,
                $certPath,
                ...$attachmentPaths,
            ]));

            return $zipPath;
        } finally {
            // Cleanup temp directory
            Storage::deleteDirectory($tempDir);
        }
    }

    protected function generateEnhancedPDS(PersonalDataSheet $pds, string $tempDir): string
    {
        // Use existing PDF generator but with enhanced template
        $pdf = $this->pdfGenerator->generate($pds);
        
        $filename = 'PDS_' . $this->sanitizeFilename($pds->full_name) . '.pdf';
        $path = $tempDir . '/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return $path;
    }

    protected function generateWorkExperienceSheet(PersonalDataSheet $pds, string $tempDir): ?string
    {
        $workExperiences = $pds->workExperiences;

        // Only generate WES if there are work experiences that don't fit on main form
        if ($workExperiences->count() <= 5) {
            return null;
        }

        $pdf = new \TCPDF('P', 'mm', 'Legal', true, 'UTF-8');
        $pdf->SetCreator('PDS Management System');
        $pdf->SetAuthor($pds->agency->name ?? 'Government Agency');
        $pdf->SetTitle('Work Experience Sheet - ' . $pds->full_name);

        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);

        // Header
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'WORK EXPERIENCE SHEET', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, 'Name: ' . $pds->full_name, 0, 1);
        $pdf->Ln(5);

        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 7, 'Inclusive Dates', 1, 0, 'C');
        $pdf->Cell(60, 7, 'Position Title', 1, 0, 'C');
        $pdf->Cell(50, 7, 'Department/Agency', 1, 0, 'C');
        $pdf->Cell(30, 7, 'Monthly Salary', 1, 1, 'C');

        // Table content
        $pdf->SetFont('Arial', '', 8);
        foreach ($workExperiences as $we) {
            $dates = ($we->from_date ?? '') . ' to ' . ($we->to_date ?? 'Present');
            $pdf->Cell(40, 6, $dates, 1, 0);
            $pdf->Cell(60, 6, $we->position_title ?? '', 1, 0);
            $pdf->Cell(50, 6, $we->department_agency ?? '', 1, 0);
            $pdf->Cell(30, 6, $we->monthly_salary ?? '', 1, 1, 'R');
        }

        // Add certification hash
        $hash = $this->generateCertificationHash($pds);
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'I', 7);
        $pdf->Cell(0, 5, 'Certification Hash: ' . $hash, 0, 1);

        $filename = 'WES_' . $this->sanitizeFilename($pds->full_name) . '.pdf';
        $path = $tempDir . '/' . $filename;
        
        Storage::put($path, $pdf->Output('', 'S'));
        
        return $path;
    }

    protected function collectAttachments(PersonalDataSheet $pds, string $tempDir): array
    {
        $attachments = [];
        $documentUploads = $pds->documentUploads;

        foreach ($documentUploads as $doc) {
            if (Storage::exists($doc->file_path)) {
                $filename = $doc->document_type . '_' . basename($doc->file_path);
                $newPath = $tempDir . '/attachments/' . $filename;
                
                Storage::copy($doc->file_path, $newPath);
                $attachments[] = $newPath;
            }
        }

        return $attachments;
    }

    protected function generateCertificationDocument(PersonalDataSheet $pds, string $tempDir): string
    {
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('PDS Management System');
        $pdf->SetTitle('Document Certification');

        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'DOCUMENT CERTIFICATION', 0, 1, 'C');
        
        $pdf->Ln(10);
        $pdf->SetFont('Arial', '', 11);
        
        $hash = $this->generateCertificationHash($pds);
        $timestamp = now()->format('Y-m-d H:i:s');
        
        $certText = <<<EOT
This certifies that the attached Personal Data Sheet (PDS) and supporting documents 
have been generated from the official PDS Management System.

Employee Name: {$pds->full_name}
Employee ID: {$pds->agency_employee_no}
Agency: {$pds->agency->name}

Document Hash: {$hash}
Generated: {$timestamp}

This certification hash can be used to verify the authenticity and integrity 
of this document package.

For verification, contact the issuing agency or visit the PDS verification portal.
EOT;

        $pdf->MultiCell(0, 5, $certText, 0, 'L');
        
        // Add watermark if approved (using standard TCPDF methods)
        if ($pds->approval_status === 'approved') {
            $pdf->SetAlpha(0.2);
            $pdf->SetFont('Arial', 'B', 60);
            $pdf->SetTextColor(0, 128, 0);
            // Use standard text rotation
            $pdf->StartTransform();
            $pdf->Rotate(45, 105, 150);
            $pdf->Text(50, 150, 'APPROVED');
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
            $pdf->SetTextColor(0, 0, 0);
        }

        $filename = 'CERTIFICATION.pdf';
        $path = $tempDir . '/' . $filename;
        
        Storage::put($path, $pdf->Output('', 'S'));
        
        return $path;
    }

    protected function createZipBundle(PersonalDataSheet $pds, string $tempDir, array $files): string
    {
        $zipFilename = 'PDS_Bundle_' . $this->sanitizeFilename($pds->full_name) . '_' . time() . '.zip';
        $zipPath = 'bundles/' . $zipFilename;
        $fullZipPath = Storage::path($zipPath);

        Storage::makeDirectory('bundles');

        $zip = new ZipArchive();
        if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception('Could not create ZIP file');
        }

        foreach ($files as $file) {
            if (Storage::exists($file)) {
                $zip->addFile(Storage::path($file), basename($file));
            }
        }

        $zip->close();

        return $zipPath;
    }

    public function generateCertificationHash(PersonalDataSheet $pds): string
    {
        $data = json_encode([
            'pds_id' => $pds->id,
            'full_name' => $pds->full_name,
            'date_of_birth' => $pds->date_of_birth,
            'employee_no' => $pds->agency_employee_no,
            'agency_id' => $pds->agency_id,
            'timestamp' => $pds->updated_at->timestamp,
        ]);

        return hash('sha256', $data);
    }

    public function verifyCertificationHash(PersonalDataSheet $pds, string $hash): bool
    {
        return $this->generateCertificationHash($pds) === $hash;
    }

    protected function sanitizeFilename(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    }

    public function addApprovalWatermark(string $pdfPath, string $status): string
    {
        // This would add watermark to existing PDF
        // Implementation depends on PDF library capabilities
        return $pdfPath;
    }
}
