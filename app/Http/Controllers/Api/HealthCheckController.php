<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ],
        ];

        $allHealthy = collect($checks['checks'])->every(fn($check) => $check['status'] === 'healthy');
        
        if (!$allHealthy) {
            $checks['status'] = 'unhealthy';
        }

        return response()->json($checks, $allHealthy ? 200 : 503);
    }

    public function database(): JsonResponse
    {
        $result = $this->checkDatabase();
        return response()->json($result, $result['status'] === 'healthy' ? 200 : 503);
    }

    public function cache(): JsonResponse
    {
        $result = $this->checkCache();
        return response()->json($result, $result['status'] === 'healthy' ? 200 : 503);
    }

    public function queue(): JsonResponse
    {
        $result = $this->checkQueue();
        return response()->json($result, $result['status'] === 'healthy' ? 200 : 503);
    }

    public function storage(): JsonResponse
    {
        $result = $this->checkStorage();
        return response()->json($result, $result['status'] === 'healthy' ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $duration,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . time();
            $value = 'test';

            Cache::put($key, $value, 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);

            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved !== $value) {
                throw new \Exception('Cache read/write mismatch');
            }

            return [
                'status' => 'healthy',
                'response_time_ms' => $duration,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            
            // For database queue, check the jobs table
            if ($connection === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();

                return [
                    'status' => 'healthy',
                    'connection' => $connection,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                ];
            }

            return [
                'status' => 'healthy',
                'connection' => $connection,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $path = storage_path();
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);
            $usedSpace = $totalSpace - $freeSpace;
            $usedPercent = round(($usedSpace / $totalSpace) * 100, 2);

            $status = $usedPercent > 90 ? 'warning' : 'healthy';

            return [
                'status' => $status,
                'path' => $path,
                'free_space_gb' => round($freeSpace / 1024 / 1024 / 1024, 2),
                'total_space_gb' => round($totalSpace / 1024 / 1024 / 1024, 2),
                'used_percent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
}
