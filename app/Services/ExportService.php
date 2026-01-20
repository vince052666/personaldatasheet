<?php

namespace App\Services;

use App\Models\PersonalDataSheet;
use Illuminate\Support\Collection;

class ExportService
{
    public function exportPDSToPDF(PersonalDataSheet $pds): string
    {
        // This would use dompdf or similar to generate PDF
        // For now, return a placeholder path
        
        $html = view('exports.pds-pdf', compact('pds'))->render();
        
        // Using dompdf (when installed)
        // $pdf = \PDF::loadHTML($html);
        // $fileName = 'pds_' . $pds->id . '_' . time() . '.pdf';
        // $pdf->save(storage_path('app/exports/' . $fileName));
        
        return 'exports/pds_' . $pds->id . '.pdf';
    }

    public function exportPDSToExcel(Collection $pdsCollection): string
    {
        // This would use maatwebsite/excel to generate Excel file
        // For now, return a placeholder
        
        $data = $this->preparePDSDataForExport($pdsCollection);
        
        // Using Laravel Excel (when installed)
        // Excel::store(new PDSExport($data), 'pds_export_' . time() . '.xlsx', 'exports');
        
        return 'exports/pds_export_' . time() . '.xlsx';
    }

    public function exportQualityReportToPDF(array $qualityData): string
    {
        $html = view('exports.quality-report-pdf', ['data' => $qualityData])->render();
        
        // Using dompdf (when installed)
        // $pdf = \PDF::loadHTML($html);
        // $fileName = 'quality_report_' . time() . '.pdf';
        // $pdf->save(storage_path('app/exports/' . $fileName));
        
        return 'exports/quality_report_' . time() . '.pdf';
    }

    public function exportQualityReportToExcel(array $qualityData): string
    {
        // Using Laravel Excel (when installed)
        // Excel::store(new QualityReportExport($qualityData), 'quality_report_' . time() . '.xlsx', 'exports');
        
        return 'exports/quality_report_' . time() . '.xlsx';
    }

    protected function preparePDSDataForExport(Collection $pdsCollection): array
    {
        return $pdsCollection->map(function ($pds) {
            return [
                'ID' => $pds->id,
                'Surname' => $pds->surname,
                'First Name' => $pds->first_name,
                'Middle Name' => $pds->middle_name,
                'Date of Birth' => $pds->date_of_birth?->format('Y-m-d'),
                'Sex' => $pds->sex,
                'Civil Status' => $pds->civil_status,
                'Email' => $pds->email_address,
                'Mobile' => $pds->mobile_no,
                'Department' => $pds->department,
                'Position' => $pds->position,
                'Status' => $pds->status,
                'Quality Score' => $pds->dataQualityScore?->overall_score,
                'Created At' => $pds->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    public function batchExportToPDF(Collection $pdsCollection): array
    {
        $files = [];
        
        foreach ($pdsCollection as $pds) {
            $files[] = $this->exportPDSToPDF($pds);
        }
        
        return $files;
    }

    public function generateCSCForm212PDF(PersonalDataSheet $pds): string
    {
        // Generate official CSC Form 212 PDF
        $html = view('exports.csc-form-212', compact('pds'))->render();
        
        // Using dompdf with specific CSC formatting
        // $pdf = \PDF::loadHTML($html)
        //     ->setPaper('legal', 'portrait')
        //     ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        // 
        // $fileName = 'CSC_Form_212_' . $pds->surname . '_' . time() . '.pdf';
        // $pdf->save(storage_path('app/exports/' . $fileName));
        
        return 'exports/CSC_Form_212_' . $pds->id . '.pdf';
    }
}
