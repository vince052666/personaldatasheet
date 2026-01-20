<?php

namespace App\Services;

use App\Models\DocumentUpload;
use App\Models\PersonalDataSheet;
use App\Jobs\ProcessOCR;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentParserService
{
    public function parseDocument(
        PersonalDataSheet $pds,
        UploadedFile $file,
        string $documentType
    ): DocumentUpload {
        // Validate file before processing
        $this->validateFile($file);
        
        // Store the file
        $path = $file->store('documents', 'private');

        // Create document upload record
        $document = DocumentUpload::create([
            'personal_data_sheet_id' => $pds->id,
            'document_type' => $documentType,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'uploaded_by' => auth()->id(),
        ]);

        // Parse document based on type
        try {
            $parsedData = $this->parseByMimeType($file, $document->mime_type);
            
            $document->update([
                'parsed_data' => $parsedData,
                'status' => 'completed',
            ]);
            
            // Queue OCR processing for images and PDFs
            if ($this->supportsOCR($document->mime_type)) {
                ProcessOCR::dispatch($document);
            }
        } catch (\Exception $e) {
            $document->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $document;
    }

    protected function supportsOCR(string $mimeType): bool
    {
        return str_contains($mimeType, 'pdf') || 
               str_contains($mimeType, 'image');
    }

    protected function validateFile(UploadedFile $file): void
    {
        $allowedMimes = config('pds.upload.allowed_mime_types', [
            'application/pdf',
            'image/jpeg',
            'image/jpg',
            'image/png',
        ]);
        
        $fileMime = $file->getMimeType();
        
        if (!in_array($fileMime, $allowedMimes)) {
            throw new \InvalidArgumentException(
                'File type not allowed. Allowed types: ' . implode(', ', $allowedMimes)
            );
        }
        
        // Verify file signature matches MIME type (basic check)
        $fileSignature = $this->getFileSignature($file);
        if (!$this->verifyFileSignature($fileSignature, $fileMime)) {
            throw new \InvalidArgumentException('File content does not match file type');
        }
        
        $maxSize = config('pds.upload.max_size', 10240) * 1024; // Convert to bytes
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size');
        }
    }

    protected function getFileSignature(UploadedFile $file): string
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $signature = fread($handle, 8);
        fclose($handle);
        
        return bin2hex($signature);
    }

    protected function verifyFileSignature(string $signature, string $mimeType): bool
    {
        $signatures = [
            'application/pdf' => ['255044462d'], // %PDF-
            'image/jpeg' => ['ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2'],
            'image/png' => ['89504e470d0a1a0a'],
        ];
        
        foreach ($signatures as $mime => $sigs) {
            if (str_contains($mimeType, $mime)) {
                foreach ($sigs as $sig) {
                    if (str_starts_with($signature, $sig)) {
                        return true;
                    }
                }
                return false;
            }
        }
        
        return false;
    }

    protected function parseByMimeType(UploadedFile $file, string $mimeType): array
    {
        return match (true) {
            str_contains($mimeType, 'pdf') => $this->parsePDF($file),
            str_contains($mimeType, 'word') || str_contains($mimeType, 'document') => $this->parseWord($file),
            str_contains($mimeType, 'image') => $this->parseImage($file),
            default => throw new \Exception('Unsupported file type'),
        };
    }

    protected function parsePDF(UploadedFile $file): array
    {
        // Placeholder for PDF parsing logic
        // You would use libraries like smalot/pdfparser or similar
        
        return [
            'type' => 'pdf',
            'extracted_text' => 'PDF parsing queued for OCR processing',
            'metadata' => [
                'pages' => 0,
                'size' => $file->getSize(),
                'ocr_queued' => true,
            ],
        ];
    }

    protected function parseWord(UploadedFile $file): array
    {
        // Placeholder for Word document parsing
        // You would use libraries like PHPWord or similar
        
        return [
            'type' => 'word',
            'extracted_text' => 'Word parsing not yet implemented',
            'metadata' => [
                'size' => $file->getSize(),
            ],
        ];
    }

    protected function parseImage(UploadedFile $file): array
    {
        // Get basic image info
        try {
            [$width, $height] = getimagesize($file->getRealPath());
        } catch (\Exception $e) {
            $width = 0;
            $height = 0;
        }
        
        return [
            'type' => 'image',
            'extracted_text' => 'Image queued for OCR processing',
            'metadata' => [
                'width' => $width,
                'height' => $height,
                'size' => $file->getSize(),
                'ocr_queued' => true,
            ],
        ];
    }

    public function extractPDSData(array $parsedData): array
    {
        // Placeholder for extracting PDS-specific data from parsed content
        // This would use pattern matching, ML, or other techniques to extract
        // structured data from the parsed document
        
        return [
            'surname' => null,
            'first_name' => null,
            'middle_name' => null,
            // ... other fields
        ];
    }
}
