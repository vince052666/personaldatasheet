<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Services\ReconciliationService;
use Illuminate\Console\Command;

class GenerateDataQualityReport extends Command
{
    protected $signature = 'pds:quality-report 
                            {agency : Agency code}
                            {--output= : Output file path (default: storage/reports/)}';

    protected $description = 'Generate data quality report for an agency';

    protected $reconciliationService;

    public function __construct(ReconciliationService $reconciliationService)
    {
        parent::__construct();
        $this->reconciliationService = $reconciliationService;
    }

    public function handle()
    {
        $agencyCode = strtoupper($this->argument('agency'));
        $agency = Agency::where('code', $agencyCode)->first();

        if (!$agency) {
            $this->error("Agency '{$agencyCode}' not found!");
            return 1;
        }

        $this->info("Generating data quality report for {$agency->name}...");

        $qualityScore = $this->reconciliationService->generateDataQualityScore($agency);
        $duplicates = $this->reconciliationService->generateDuplicateReport($agency);

        $this->newLine();
        $this->info("=== DATA QUALITY REPORT ===");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Records', $qualityScore['total_records']],
                ['Overall Score', $qualityScore['overall_score'] . '%'],
                ['Grade', $qualityScore['grade']],
                ['Completeness', round($qualityScore['breakdown']['completeness'], 2) . '%'],
                ['Accuracy', round($qualityScore['breakdown']['accuracy'], 2) . '%'],
                ['Consistency', round($qualityScore['breakdown']['consistency'], 2) . '%'],
            ]
        );

        $this->newLine();
        $this->info("=== DUPLICATE ANALYSIS ===");
        $this->table(
            ['Type', 'Count'],
            [
                ['Email Duplicates', $duplicates['email_duplicates']],
                ['SSN Duplicates', $duplicates['ssn_duplicates']],
                ['Total Potential Duplicates', $duplicates['total_potential_duplicates']],
            ]
        );

        // Save to file if requested
        if ($this->option('output')) {
            $outputPath = $this->option('output');
        } else {
            $outputPath = storage_path('reports/quality_' . $agencyCode . '_' . date('Y-m-d') . '.json');
        }

        $reportData = [
            'agency' => [
                'code' => $agency->code,
                'name' => $agency->name,
            ],
            'quality_score' => $qualityScore,
            'duplicates' => $duplicates,
            'generated_at' => now()->toIso8601String(),
        ];

        file_put_contents($outputPath, json_encode($reportData, JSON_PRETTY_PRINT));
        $this->info("Report saved to: {$outputPath}");

        return 0;
    }
}
