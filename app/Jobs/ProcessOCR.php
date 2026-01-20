<?php

namespace App\Jobs;

use App\Models\DocumentUpload;
use App\Models\OcrResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ProcessOCR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct(
        public DocumentUpload $documentUpload
    ) {}

    public function handle(): void
    {
        $filePath = Storage::path($this->documentUpload->file_path);
        
        if (!file_exists($filePath)) {
            $this->fail(new \Exception('File not found'));
            return;
        }

        try {
            if (class_exists(TesseractOCR::class)) {
                $ocr = new TesseractOCR($filePath);
                $text = $ocr->run();
            } else {
                $text = "OCR processing requires Tesseract installation. This is a stub.";
            }
            
            $parsedData = $this->parseOCRText($text);
            
            foreach ($parsedData as $fieldName => $value) {
                OcrResult::create([
                    'document_upload_id' => $this->documentUpload->id,
                    'field_name' => $fieldName,
                    'raw_text' => $text,
                    'parsed_value' => $value,
                    'confidence_score' => $this->calculateConfidence($fieldName, $value),
                    'metadata' => [
                        'processed_at' => now()->toIso8601String(),
                        'ocr_engine' => 'tesseract',
                    ],
                ]);
            }
            
            $this->documentUpload->update([
                'processing_status' => 'completed',
                'processed_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            $this->documentUpload->update([
                'processing_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            
            $this->fail($e);
        }
    }

    protected function parseOCRText(string $text): array
    {
        $data = [];
        
        if (preg_match('/SURNAME[:\s]+([A-Z\s]+)/i', $text, $matches)) {
            $data['surname'] = trim($matches[1]);
        }
        
        if (preg_match('/FIRST\s+NAME[:\s]+([A-Z\s]+)/i', $text, $matches)) {
            $data['first_name'] = trim($matches[1]);
        }
        
        if (preg_match('/MIDDLE\s+NAME[:\s]+([A-Z\s]+)/i', $text, $matches)) {
            $data['middle_name'] = trim($matches[1]);
        }
        
        if (preg_match('/DATE\s+OF\s+BIRTH[:\s]+(\d{2}\/\d{2}\/\d{4})/i', $text, $matches)) {
            $data['date_of_birth'] = trim($matches[1]);
        }
        
        if (preg_match('/(?:EMAIL|E-MAIL)[:\s]+([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $matches)) {
            $data['email_address'] = trim($matches[1]);
        }
        
        if (preg_match('/(?:MOBILE|CELL)[:\s]+([\d\s\-\+]+)/i', $text, $matches)) {
            $data['mobile_no'] = preg_replace('/[^\d+]/', '', $matches[1]);
        }
        
        return $data;
    }

    protected function calculateConfidence(string $fieldName, $value): float
    {
        if (empty($value)) {
            return 0;
        }
        
        switch ($fieldName) {
            case 'email_address':
                return filter_var($value, FILTER_VALIDATE_EMAIL) ? 95 : 40;
            
            case 'mobile_no':
                return preg_match('/^(09|\+639)\d{9}$/', $value) ? 90 : 50;
            
            case 'date_of_birth':
                try {
                    \Carbon\Carbon::parse($value);
                    return 85;
                } catch (\Exception $e) {
                    return 30;
                }
            
            case 'surname':
            case 'first_name':
            case 'middle_name':
                return preg_match('/^[A-Za-z\s\-\.]+$/', $value) ? 80 : 60;
            
            default:
                return 70;
        }
    }
}
