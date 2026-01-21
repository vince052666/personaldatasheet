<?php

namespace App\Services;

use App\Models\PersonalDataSheet;

class PDFGeneratorService
{
    public function generatePDF(PersonalDataSheet $pds): string
    {
        // Load all relationships
        $pds->load([
            'workExperiences',
            'educationalBackgrounds',
            'civilServiceEligibilities',
            'trainings',
            'voluntaryWorks',
            'otherInformation',
            'user',
        ]);

        // This is a placeholder for actual PDF generation
        // You would typically use libraries like dompdf, mpdf, or snappy
        // For now, we'll return a path where the PDF would be saved
        
        $pdfContent = $this->generatePDFContent($pds);
        $filename = 'pds_' . $pds->user->employee_id . '_' . time() . '.pdf';
        $path = storage_path('app/public/pdfs/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // In a real implementation, you would use a PDF library here
        // For example with dompdf:
        // $pdf = \PDF::loadView('pdf.pds', compact('pds'));
        // $pdf->save($path);
        
        // Placeholder: save as text for now
        file_put_contents($path, $pdfContent);

        return $path;
    }

    protected function generatePDFContent(PersonalDataSheet $pds): string
    {
        $content = "PERSONAL DATA SHEET\n";
        $content .= "Civil Service Form No. 212\n\n";
        
        $content .= "I. PERSONAL INFORMATION\n";
        $content .= "Name: {$pds->full_name}\n";
        $content .= "Date of Birth: {$pds->date_of_birth->format('m/d/Y')}\n";
        $content .= "Place of Birth: {$pds->place_of_birth}\n";
        $content .= "Sex: {$pds->sex}\n";
        $content .= "Civil Status: {$pds->civil_status}\n";
        $content .= "Citizenship: {$pds->citizenship}\n\n";

        $content .= "II. EDUCATIONAL BACKGROUND\n";
        foreach ($pds->educationalBackgrounds as $education) {
            $content .= "{$education->level}: {$education->school_name}\n";
            $content .= "Course: {$education->degree_course}\n";
            $content .= "Year Graduated: {$education->year_graduated}\n\n";
        }

        $content .= "III. WORK EXPERIENCE\n";
        foreach ($pds->workExperiences as $work) {
            $content .= "{$work->position_title} at {$work->company}\n";
            $content .= "From: {$work->from_date->format('m/Y')} To: ";
            $content .= $work->is_present ? 'Present' : $work->to_date->format('m/Y');
            $content .= "\n\n";
        }

        return $content;
    }

    public function getCSCFormTemplate(): array
    {
        // Return CSC Form 212 template structure
        return [
            'sections' => [
                'personal_information' => [
                    'surname',
                    'first_name',
                    'middle_name',
                    'name_extension',
                    'date_of_birth',
                    'place_of_birth',
                    'sex',
                    'civil_status',
                    'height',
                    'weight',
                    'blood_type',
                ],
                'family_background' => [
                    'spouse',
                    'father',
                    'mother',
                    'children',
                ],
                'educational_background' => [
                    'elementary',
                    'secondary',
                    'vocational',
                    'college',
                    'graduate_studies',
                ],
                'civil_service_eligibility' => [],
                'work_experience' => [],
                'voluntary_work' => [],
                'learning_and_development' => [],
                'other_information' => [],
            ],
        ];
    }
}
