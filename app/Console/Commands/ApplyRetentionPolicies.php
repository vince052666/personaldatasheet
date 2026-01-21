<?php

namespace App\Console\Commands;

use App\Services\DataRetentionService;
use Illuminate\Console\Command;

class ApplyRetentionPolicies extends Command
{
    protected $signature = 'retention:apply';
    protected $description = 'Apply data retention policies';

    public function handle(DataRetentionService $retentionService): int
    {
        $this->info('Applying data retention policies...');

        try {
            $results = $retentionService->applyRetentionPolicies();

            foreach ($results as $resourceType => $count) {
                $this->info("Archived {$count} {$resourceType} record(s)");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to apply retention policies: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
