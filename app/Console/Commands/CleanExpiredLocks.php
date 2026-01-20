<?php

namespace App\Console\Commands;

use App\Services\RecordLockService;
use Illuminate\Console\Command;

class CleanExpiredLocks extends Command
{
    protected $signature = 'locks:clean';
    protected $description = 'Clean expired record locks';

    public function handle(RecordLockService $lockService): int
    {
        $this->info('Cleaning expired locks...');

        try {
            $count = $lockService->cleanExpiredLocks();

            $this->info("Cleaned {$count} expired lock(s)");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to clean locks: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
