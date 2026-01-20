<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CleanOldBackups extends Command
{
    protected $signature = 'backup:clean {--days=30 : Number of days to keep backups}';
    protected $description = 'Clean old backup files';

    public function handle(BackupService $backupService): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning backups older than {$days} days...");

        try {
            $count = $backupService->cleanOldBackups($days);

            $this->info("Cleaned {$count} old backup(s)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
