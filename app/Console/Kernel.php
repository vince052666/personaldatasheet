<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\BackupDatabase::class,
        Commands\BackupFiles::class,
        Commands\CleanOldBackups::class,
        Commands\ApplyRetentionPolicies::class,
        Commands\DeleteExpiredArchives::class,
        Commands\CleanExpiredLocks::class,
        Commands\SetupPermissions::class,
        Commands\VerifyAuditChain::class,
        Commands\RankCandidates::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Daily database backup at 2 AM
        $schedule->command('backup:database --verify')
            ->dailyAt('02:00')
            ->onFailure(function () {
                \Log::error('Database backup failed');
            });

        // Weekly file backup on Sunday at 3 AM
        $schedule->command('backup:files')
            ->weekly()
            ->sundays()
            ->at('03:00');

        // Clean old backups monthly
        $schedule->command('backup:clean --days=30')
            ->monthly();

        // Apply retention policies daily at 1 AM
        $schedule->command('retention:apply')
            ->dailyAt('01:00');

        // Clean expired locks every hour
        $schedule->command('locks:clean')
            ->hourly();

        // Verify audit chain weekly
        $schedule->command('audit:verify')
            ->weekly()
            ->fridays()
            ->at('00:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
