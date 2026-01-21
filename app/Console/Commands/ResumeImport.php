<?php

namespace App\Console\Commands;

use App\Models\ImportBatch;
use App\Services\DataMigrationService;
use Illuminate\Console\Command;

class ResumeImport extends Command
{
    protected $signature = 'pds:import-resume 
                            {batch-id : Import batch ID to resume}
                            {--chunk=100 : Number of records to process per batch}';

    protected $description = 'Resume a previously started import';

    protected $migrationService;

    public function __construct(DataMigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    public function handle()
    {
        $batchId = $this->argument('batch-id');
        $batch = ImportBatch::find($batchId);

        if (!$batch) {
            $this->error("Import batch {$batchId} not found!");
            return 1;
        }

        if ($batch->isComplete()) {
            $this->error("Import batch {$batchId} is already complete!");
            return 1;
        }

        $this->info("Resuming import batch {$batchId}");
        $this->info("File: {$batch->filename}");
        $this->info("Progress: {$batch->processed_records}/{$batch->total_records}");

        $chunkSize = (int) $this->option('chunk');
        
        $this->migrationService->resumeBatch($batch, $chunkSize);

        $this->newLine();
        $this->info("Import resumed and completed!");

        $summary = $this->migrationService->getBatchSummary($batch);
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Records', $summary['total_records']],
                ['Processed', $summary['processed_records']],
                ['Success', $summary['success_count']],
                ['Errors', $summary['error_count']],
                ['Duplicates', $summary['duplicate_count']],
                ['Status', $summary['status']],
            ]
        );

        return 0;
    }
}
