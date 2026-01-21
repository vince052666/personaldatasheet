<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupFiles extends Command
{
    protected $signature = 'backup:files {--dirs=* : Directories to backup}';
    protected $description = 'Backup application files';

    public function handle(BackupService $backupService): int
    {
        $this->info('Starting files backup...');

        try {
            $dirs = $this->option('dirs') ?: null;
            $log = $backupService->backupFiles($dirs);

            $this->info("Files backup completed successfully!");
            $this->info("File: {$log->file_path}");
            $this->info("Size: " . number_format($log->file_size / 1024 / 1024, 2) . " MB");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
