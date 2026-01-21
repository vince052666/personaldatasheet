<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\AlertingService;
use App\Services\LoggingService;
use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OpsConsoleController extends Controller
{
    protected LoggingService $logger;
    protected MetricsService $metrics;
    protected AlertingService $alerting;

    public function __construct(
        LoggingService $logger,
        MetricsService $metrics,
        AlertingService $alerting
    ) {
        $this->logger = $logger;
        $this->metrics = $metrics;
        $this->alerting = $alerting;
    }

    public function dashboard()
    {
        $health = $this->getSystemHealth();
        $queueStats = $this->getQueueStats();
        $recentAlerts = $this->getRecentAlerts();
        $backupStatus = $this->getBackupStatus();

        return view('ops.dashboard', compact('health', 'queueStats', 'recentAlerts', 'backupStatus'));
    }

    public function health()
    {
        $health = $this->getSystemHealth();

        return response()->json($health);
    }

    public function clearCache(Request $request)
    {
        $this->authorize('manage-system');

        $type = $request->input('type', 'all');

        try {
            switch ($type) {
                case 'config':
                    Artisan::call('config:clear');
                    break;
                case 'route':
                    Artisan::call('route:clear');
                    break;
                case 'view':
                    Artisan::call('view:clear');
                    break;
                case 'application':
                    Cache::flush();
                    break;
                default:
                    Artisan::call('cache:clear');
                    Cache::flush();
            }

            $this->logger->info("Cache cleared: {$type}", ['user' => auth()->user()->name]);

            return back()->with('success', "Cache cleared successfully: {$type}");
        } catch (\Exception $e) {
            $this->logger->error("Cache clear failed: {$e->getMessage()}");
            return back()->with('error', 'Failed to clear cache');
        }
    }

    public function retryFailedJobs(Request $request)
    {
        $this->authorize('manage-system');

        $jobId = $request->input('job_id');

        try {
            if ($jobId) {
                Artisan::call('queue:retry', ['id' => [$jobId]]);
            } else {
                Artisan::call('queue:retry', ['id' => ['all']]);
            }

            $this->logger->info("Failed jobs retried", ['job_id' => $jobId ?? 'all']);

            return back()->with('success', 'Failed jobs queued for retry');
        } catch (\Exception $e) {
            $this->logger->error("Job retry failed: {$e->getMessage()}");
            return back()->with('error', 'Failed to retry jobs');
        }
    }

    public function verifyBackups()
    {
        $this->authorize('manage-system');

        $backups = DB::table('backup_logs')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('ops.backups', compact('backups'));
    }

    public function triggerRetention()
    {
        $this->authorize('manage-system');

        try {
            Artisan::call('data:retention');

            $this->logger->info("Data retention triggered manually");

            return back()->with('success', 'Data retention process started');
        } catch (\Exception $e) {
            $this->logger->error("Retention trigger failed: {$e->getMessage()}");
            return back()->with('error', 'Failed to trigger retention');
        }
    }

    public function metrics()
    {
        $this->authorize('manage-system');

        $systemHealth = $this->metrics->recordSystemHealth();
        $queueMetrics = $this->getQueueMetrics();

        return view('ops.metrics', compact('systemHealth', 'queueMetrics'));
    }

    protected function getSystemHealth(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'disk' => $this->checkDisk(),
            'memory' => $this->checkMemory(),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');
            return ['status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            return ['status' => $value === 'ok' ? 'healthy' : 'unhealthy'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        $failed = DB::table('failed_jobs')->count();
        $pending = DB::table('jobs')->count();

        return [
            'status' => $failed < 10 ? 'healthy' : 'degraded',
            'failed' => $failed,
            'pending' => $pending,
        ];
    }

    protected function checkDisk(): array
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $total - $free;
        $percent = round(($used / $total) * 100, 2);

        return [
            'status' => $percent < 90 ? 'healthy' : 'critical',
            'used_percent' => $percent,
            'free_gb' => round($free / (1024 ** 3), 2),
        ];
    }

    protected function checkMemory(): array
    {
        $usage = memory_get_usage(true);
        $usageMB = round($usage / (1024 ** 2), 2);

        return [
            'status' => 'healthy',
            'usage_mb' => $usageMB,
        ];
    }

    protected function getQueueStats(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'processing' => DB::table('jobs')->where('reserved_at', '!=', null)->count(),
        ];
    }

    protected function getQueueMetrics(): array
    {
        return [
            'by_queue' => DB::table('jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->toArray(),
            'failed_by_queue' => DB::table('failed_jobs')
                ->select('queue', DB::raw('count(*) as count'))
                ->groupBy('queue')
                ->get()
                ->toArray(),
        ];
    }

    protected function getRecentAlerts(): array
    {
        return DB::table('audit_logs')
            ->where('event', 'like', 'ALERT%')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    protected function getBackupStatus(): array
    {
        $lastBackup = DB::table('backup_logs')
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'last_backup' => $lastBackup?->created_at ?? 'Never',
            'status' => $lastBackup?->status ?? 'unknown',
            'size_mb' => $lastBackup?->size_mb ?? 0,
        ];
    }
}
