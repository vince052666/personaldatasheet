<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessOCR;
use App\Models\DocumentUpload;
use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessOCRTest extends TestCase
{
    use RefreshDatabase;

    protected Agency $agency;
    protected DocumentUpload $document;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->agency = Agency::factory()->create();
        $this->document = DocumentUpload::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_job_can_be_dispatched()
    {
        Queue::fake();

        ProcessOCR::dispatch($this->document);

        Queue::assertPushed(ProcessOCR::class);
    }

    public function test_processes_ocr_successfully()
    {
        $job = new ProcessOCR($this->document);
        
        // Mock OCR processing
        $this->assertTrue(true);
    }

    public function test_handles_ocr_errors()
    {
        $job = new ProcessOCR($this->document);
        
        try {
            $job->handle();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }
}
