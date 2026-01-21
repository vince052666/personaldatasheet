<?php

namespace App\Services;

use App\Models\Agency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class AlertingService
{
    protected LoggingService $logger;
    protected MetricsService $metrics;

    public function __construct(LoggingService $logger, MetricsService $metrics)
    {
        $this->logger = $logger;
        $this->metrics = $metrics;
    }

    public function checkFailedJobs(): void
    {
        $failedCount = DB::table('failed_jobs')->count();
        $threshold = config('alerting.failed_jobs_threshold', 10);

        if ($failedCount > $threshold) {
            $this->sendAlert('failed_jobs', "Failed jobs: {$failedCount}", [
                'failed_count' => $failedCount,
                'threshold' => $threshold,
            ]);
        }
    }

    public function checkSlowQueries(): void
    {
        $queries = DB::getQueryLog();
        $slowQueries = collect($queries)->filter(fn($q) => $q['time'] > 1000);

        if ($slowQueries->count() > 5) {
            $this->sendAlert('slow_queries', "Slow queries detected: {$slowQueries->count()}", [
                'slow_query_count' => $slowQueries->count(),
                'queries' => $slowQueries->take(5)->toArray(),
            ]);
        }
    }

    public function checkDiskUsage(): void
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $usedPercent = (($total - $free) / $total) * 100;

        if ($usedPercent > 90) {
            $this->sendAlert('disk_usage', "Disk usage critical: {$usedPercent}%", [
                'used_percent' => round($usedPercent, 2),
                'free_gb' => round($free / (1024 ** 3), 2),
            ]);
        }
    }

    public function checkBackupStatus(): void
    {
        $lastBackup = DB::table('backup_logs')
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastBackup || now()->diffInHours($lastBackup->created_at) > 24) {
            $this->sendAlert('backup_failure', 'Backup has not completed in 24 hours', [
                'last_backup' => $lastBackup?->created_at,
                'hours_since' => $lastBackup ? now()->diffInHours($lastBackup->created_at) : 'never',
            ]);
        }
    }

    public function checkAuditChainIntegrity(): void
    {
        $suspiciousLogs = DB::table('audit_logs')
            ->where('created_at', '>', now()->subHours(24))
            ->whereNull('user_id')
            ->orWhere(function ($query) {
                $query->whereNull('previous_hash')
                    ->where('id', '>', 1);
            })
            ->count();

        if ($suspiciousLogs > 0) {
            $this->sendAlert('audit_chain_anomaly', "Audit chain anomalies detected: {$suspiciousLogs}", [
                'anomaly_count' => $suspiciousLogs,
                'severity' => 'critical',
            ]);
        }
    }

    public function checkQueueHealth(): void
    {
        $oldestJob = DB::table('jobs')
            ->orderBy('created_at', 'asc')
            ->first();

        if ($oldestJob && now()->diffInHours($oldestJob->created_at) > 2) {
            $this->sendAlert('queue_stuck', 'Jobs stuck in queue', [
                'oldest_job_age_hours' => now()->diffInHours($oldestJob->created_at),
                'queue_size' => DB::table('jobs')->count(),
            ]);
        }
    }

    public function checkImportBatchHealth(int $batchId): void
    {
        $batch = DB::table('import_batches')->find($batchId);

        if (!$batch) {
            return;
        }

        if ($batch->status === 'processing' && now()->diffInHours($batch->updated_at) > 1) {
            $this->sendAlert('import_stuck', "Import batch {$batchId} stuck", [
                'batch_id' => $batchId,
                'hours_processing' => now()->diffInHours($batch->updated_at),
            ]);
        }

        $errorRate = $batch->failed_count / max($batch->total_count, 1);
        if ($errorRate > 0.2) {
            $this->sendAlert('import_high_errors', "Import batch {$batchId} high error rate", [
                'batch_id' => $batchId,
                'error_rate' => round($errorRate * 100, 2) . '%',
                'failed_count' => $batch->failed_count,
            ]);
        }
    }

    protected function sendAlert(string $type, string $message, array $context = []): void
    {
        $this->logger->critical("ALERT: {$message}", array_merge([
            'alert_type' => $type,
        ], $context));

        $this->metrics->incrementCounter('alerts.triggered', [
            'type' => $type,
        ]);

        $recipients = config('alerting.recipients', []);

        if (empty($recipients)) {
            return;
        }

        try {
            Mail::raw(
                $this->formatAlertEmail($type, $message, $context),
                function ($mail) use ($recipients, $type) {
                    $mail->to($recipients)
                        ->subject("[PDS Alert] {$type}")
                        ->priority(1);
                }
            );
        } catch (\Exception $e) {
            $this->logger->error("Failed to send alert email: {$e->getMessage()}");
        }
    }

    protected function formatAlertEmail(string $type, string $message, array $context): string
    {
        $contextStr = json_encode($context, JSON_PRETTY_PRINT);

        return <<<EMAIL
        PDS Management System Alert
        
        Type: {$type}
        Message: {$message}
        Time: {now()->toDateTimeString()}
        
        Details:
        {$contextStr}
        
        Please investigate immediately.
        EMAIL;
    }

    public function runAllChecks(): array
    {
        $results = [];

        try {
            $this->checkFailedJobs();
            $results['failed_jobs'] = 'passed';
        } catch (\Exception $e) {
            $results['failed_jobs'] = 'failed: ' . $e->getMessage();
        }

        try {
            $this->checkDiskUsage();
            $results['disk_usage'] = 'passed';
        } catch (\Exception $e) {
            $results['disk_usage'] = 'failed: ' . $e->getMessage();
        }

        try {
            $this->checkBackupStatus();
            $results['backup_status'] = 'passed';
        } catch (\Exception $e) {
            $results['backup_status'] = 'failed: ' . $e->getMessage();
        }

        try {
            $this->checkAuditChainIntegrity();
            $results['audit_chain'] = 'passed';
        } catch (\Exception $e) {
            $results['audit_chain'] = 'failed: ' . $e->getMessage();
        }

        try {
            $this->checkQueueHealth();
            $results['queue_health'] = 'passed';
        } catch (\Exception $e) {
            $results['queue_health'] = 'failed: ' . $e->getMessage();
        }

        return $results;
    }
}
