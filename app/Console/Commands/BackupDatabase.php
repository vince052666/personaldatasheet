<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--verify : Verify backup after creation}';
    protected $description = 'Backup the database';

    public function handle(BackupService $backupService): int
    {
        $this->info('Starting database backup...');

        try {
            $log = $backupService->backupDatabase();

            $this->info("Database backup completed successfully!");
            $this->info("File: {$log->file_path}");
            $this->info("Size: " . number_format($log->file_size / 1024 / 1024, 2) . " MB");
            $this->info("Checksum: {$log->checksum}");

            if ($this->option('verify')) {
                $this->info('Verifying backup...');
                if ($backupService->verifyBackup($log)) {
                    $this->info('Backup verification successful!');
                } else {
                    $this->error('Backup verification failed!');
                    return Command::FAILURE;
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
