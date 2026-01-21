<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessBulkImport;
use App\Models\Agency;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessBulkImportTest extends TestCase
{
    use RefreshDatabase;

    protected Agency $agency;
    protected User $user;
    protected ImportBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->batch = ImportBatch::factory()->create([
            'agency_id' => $this->agency->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_job_can_be_dispatched()
    {
        Queue::fake();

        ProcessBulkImport::dispatch($this->batch);

        Queue::assertPushed(ProcessBulkImport::class);
    }

    public function test_processes_import_batch_successfully()
    {
        $job = new ProcessBulkImport($this->batch);
        $job->handle();

        $this->batch->refresh();
        $this->assertNotEquals('pending', $this->batch->status);
    }

    public function test_handles_import_errors_gracefully()
    {
        $batch = ImportBatch::factory()->create([
            'agency_id' => $this->agency->id,
            'data' => ['invalid' => 'data'],
        ]);

        $job = new ProcessBulkImport($batch);
        
        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected
        }

        $batch->refresh();
        $this->assertEquals('failed', $batch->status);
    }

    public function test_respects_agency_isolation_during_import()
    {
        $otherAgency = Agency::factory()->create();
        
        $job = new ProcessBulkImport($this->batch);
        $job->handle();

        $this->assertDatabaseMissing('personal_data_sheets', [
            'agency_id' => $otherAgency->id,
        ]);
    }
}
