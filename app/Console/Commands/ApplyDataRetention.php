<?php

namespace App\Console\Commands;

use App\Models\DataRetention;
use App\Models\PersonalDataSheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApplyDataRetention extends Command
{
    protected $signature = 'data:retention {--dry-run : Show what would be deleted without actually deleting}';
    protected $description = 'Apply data retention policies';

    public function handle(): int
    {
        $this->info('Applying data retention policies...');

        $dryRun = $this->option('dry-run');

        // Separated employees - 10 years retention
        $separatedEmployeesQuery = PersonalDataSheet::onlyTrashed()
            ->where('deleted_at', '<', now()->subYears(10));

        $separatedCount = $separatedEmployeesQuery->count();

        if ($separatedCount > 0) {
            $this->warn("Found {$separatedCount} PDS records past retention period (separated > 10 years)");

            if (!$dryRun) {
                $separatedEmployeesQuery->forceDelete();
                $this->info("Permanently deleted {$separatedCount} records");
            }
        }

        // Rejected applications - 1 year retention
        $rejectedApplicationsQuery = PersonalDataSheet::where('status', 'rejected')
            ->where('updated_at', '<', now()->subYear());

        $rejectedCount = $rejectedApplicationsQuery->count();

        if ($rejectedCount > 0) {
            $this->warn("Found {$rejectedCount} rejected applications past retention period (> 1 year)");

            if (!$dryRun) {
                $rejectedApplicationsQuery->delete();
                $this->info("Deleted {$rejectedCount} rejected applications");
            }
        }

        // Application logs - 90 days retention
        $oldLogsCount = DB::table('activity_logs')
            ->where('created_at', '<', now()->subDays(90))
            ->count();

        if ($oldLogsCount > 0) {
            $this->warn("Found {$oldLogsCount} activity logs past retention period (> 90 days)");

            if (!$dryRun) {
                DB::table('activity_logs')
                    ->where('created_at', '<', now()->subDays(90))
                    ->delete();
                $this->info("Deleted {$oldLogsCount} old activity logs");
            }
        }

        if ($dryRun) {
            $this->info('Dry run completed. No data was actually deleted.');
        } else {
            $this->info('Data retention policies applied successfully.');
        }

        return Command::SUCCESS;
    }
}
