<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\ImportBatch;
use App\Models\User;
use App\Services\DataMigrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportPdsData extends Command
{
    protected $signature = 'pds:import 
                            {agency : Agency code}
                            {file : Path to Excel/CSV file}
                            {--user-id= : User ID who is importing (default: 1)}
                            {--chunk=100 : Number of records to process per batch}';

    protected $description = 'Import PDS data from Excel/CSV file';

    protected $migrationService;

    public function __construct(DataMigrationService $migrationService)
    {
        parent::__construct();
        $this->migrationService = $migrationService;
    }

    public function handle()
    {
        $agencyCode = strtoupper($this->argument('agency'));
        $filePath = $this->argument('file');

        // Validate agency
        $agency = Agency::where('code', $agencyCode)->first();
        if (!$agency) {
            $this->error("Agency '{$agencyCode}' not found!");
            return 1;
        }

        // Validate file
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Get user
        $userId = $this->option('user-id') ?? 1;
        $user = User::find($userId);
        if (!$user) {
            $this->error("User ID {$userId} not found!");
            return 1;
        }

        $this->info("Starting import for agency: {$agency->name}");
        $this->info("File: {$filePath}");

        // Read file and parse
        $records = $this->parseFile($filePath);
        
        if (empty($records)) {
            $this->error("No records found in file!");
            return 1;
        }

        $this->info("Found " . count($records) . " records");

        // Create batch
        $batch = $this->migrationService->createBatch(
            $agency,
            $user,
            basename($filePath),
            count($records)
        );

        $this->info("Created import batch ID: {$batch->id}");

        // Stage records
        $this->info("Staging records...");
        $this->migrationService->stageRecords($batch, $records);
        $this->info("Records staged successfully");

        // Process staging
        $chunkSize = (int) $this->option('chunk');
        $this->info("Processing records (chunk size: {$chunkSize})...");
        
        $this->withProgressBar($batch->total_records, function () use ($batch, $chunkSize) {
            $this->migrationService->processStaging($batch, $chunkSize);
        });

        $this->newLine(2);

        // Show summary
        $summary = $this->migrationService->getBatchSummary($batch);
        
        $this->info("Import completed!");
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

        if ($summary['error_count'] > 0) {
            $this->warn("Import completed with errors. Check import_errors table for details.");
        }

        return 0;
    }

    protected function parseFile(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        if ($extension === 'csv') {
            return $this->parseCsv($filePath);
        } elseif (in_array($extension, ['xlsx', 'xls'])) {
            return $this->parseExcel($filePath);
        }

        throw new \Exception("Unsupported file format: {$extension}");
    }

    protected function parseCsv(string $filePath): array
    {
        $records = [];
        $header = null;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (!$header) {
                    $header = $row;
                } else {
                    $records[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $records;
    }

    protected function parseExcel(string $filePath): array
    {
        // Simplified - in production use PhpSpreadsheet
        $this->warn("Excel parsing not fully implemented. Please use CSV format.");
        return [];
    }
}
