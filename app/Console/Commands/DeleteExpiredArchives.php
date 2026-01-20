<?php

namespace App\Console\Commands;

use App\Services\DataRetentionService;
use Illuminate\Console\Command;

class DeleteExpiredArchives extends Command
{
    protected $signature = 'retention:delete-expired';
    protected $description = 'Delete expired archived records';

    public function handle(DataRetentionService $retentionService): int
    {
        $this->info('Deleting expired archives...');

        if (!$this->confirm('This will permanently delete expired archived records. Continue?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        try {
            $count = $retentionService->deleteExpiredArchives();

            $this->info("Deleted {$count} expired archive(s)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to delete expired archives: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
