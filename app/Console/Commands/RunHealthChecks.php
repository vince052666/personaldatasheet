<?php

namespace App\Console\Commands;

use App\Services\AlertingService;
use Illuminate\Console\Command;

class RunHealthChecks extends Command
{
    protected $signature = 'health:check {--notify : Send notifications if issues found}';
    protected $description = 'Run all health checks and optionally send alerts';

    public function handle(AlertingService $alerting): int
    {
        $this->info('Running health checks...');

        $results = $alerting->runAllChecks();

        foreach ($results as $check => $status) {
            if (str_starts_with($status, 'failed')) {
                $this->error("❌ {$check}: {$status}");
            } else {
                $this->info("✅ {$check}: {$status}");
            }
        }

        $failedCount = collect($results)->filter(fn($s) => str_starts_with($s, 'failed'))->count();

        if ($failedCount > 0) {
            $this->warn("Health checks completed with {$failedCount} failures");
            return Command::FAILURE;
        }

        $this->info('All health checks passed!');
        return Command::SUCCESS;
    }
}
