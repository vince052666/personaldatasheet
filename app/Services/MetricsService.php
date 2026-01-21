<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MetricsService
{
    protected LoggingService $logger;

    public function __construct(LoggingService $logger)
    {
        $this->logger = $logger;
    }

    public function recordMetric(string $name, $value, array $tags = []): void
    {
        $key = $this->buildMetricKey($name, $tags);
        $timestamp = now()->toIso8601String();

        $metric = [
            'name' => $name,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => $timestamp,
            'correlation_id' => $this->logger->getCorrelationId(),
        ];

        Cache::tags(['metrics'])->put($key, $metric, now()->addHours(24));

        $this->logger->debug("Metric recorded: {$name}", $metric);
    }

    public function incrementCounter(string $name, array $tags = []): void
    {
        $key = $this->buildMetricKey($name, $tags);
        $current = Cache::get($key, 0);
        Cache::put($key, $current + 1, now()->addHours(24));
    }

    public function recordTiming(string $operation, float $duration, array $tags = []): void
    {
        $this->recordMetric("timing.{$operation}", $duration, $tags);
    }

    public function recordQueueMetrics(): void
    {
        $queueSizes = [
            'default' => DB::table('jobs')->where('queue', 'default')->count(),
            'imports' => DB::table('jobs')->where('queue', 'imports')->count(),
            'ocr' => DB::table('jobs')->where('queue', 'ocr')->count(),
            'reports' => DB::table('jobs')->where('queue', 'reports')->count(),
        ];

        foreach ($queueSizes as $queue => $size) {
            $this->recordMetric('queue.size', $size, ['queue' => $queue]);
        }

        $failedJobs = DB::table('failed_jobs')->count();
        $this->recordMetric('queue.failed', $failedJobs);
    }

    public function recordDatabaseMetrics(): void
    {
        $queries = DB::getQueryLog();
        $slowQueries = collect($queries)->filter(fn($q) => $q['time'] > 1000)->count();

        $this->recordMetric('database.slow_queries', $slowQueries);
        $this->recordMetric('database.total_queries', count($queries));
    }

    public function recordImportBatchMetrics(int $batchId, array $stats): void
    {
        $this->recordMetric('import.batch.records_processed', $stats['processed'] ?? 0, [
            'batch_id' => $batchId,
        ]);

        $this->recordMetric('import.batch.records_failed', $stats['failed'] ?? 0, [
            'batch_id' => $batchId,
        ]);

        $this->recordMetric('import.batch.duration', $stats['duration'] ?? 0, [
            'batch_id' => $batchId,
        ]);
    }

    public function recordApiMetrics(string $endpoint, int $statusCode, float $responseTime): void
    {
        $this->recordMetric('api.response_time', $responseTime, [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
        ]);

        $this->incrementCounter('api.requests', [
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
        ]);
    }

    public function getMetrics(string $name, array $tags = [], int $hours = 24): array
    {
        $pattern = $this->buildMetricKey($name, $tags);
        $cacheKeys = Cache::tags(['metrics'])->get($pattern, []);

        return collect($cacheKeys)
            ->filter(fn($metric) => $metric['timestamp'] > now()->subHours($hours)->toIso8601String())
            ->values()
            ->toArray();
    }

    protected function buildMetricKey(string $name, array $tags = []): string
    {
        $tagString = collect($tags)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode(',');

        return "metrics:{$name}" . ($tagString ? ":{$tagString}" : '');
    }

    public function recordSystemHealth(): array
    {
        $health = [
            'disk_usage' => $this->getDiskUsage(),
            'memory_usage' => memory_get_usage(true),
            'queue_health' => $this->getQueueHealth(),
            'database_health' => $this->getDatabaseHealth(),
        ];

        $this->recordMetric('system.disk_usage_percent', $health['disk_usage']);
        $this->recordMetric('system.memory_usage_bytes', $health['memory_usage']);

        return $health;
    }

    protected function getDiskUsage(): float
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        return round((($total - $free) / $total) * 100, 2);
    }

    protected function getQueueHealth(): string
    {
        $failedJobs = DB::table('failed_jobs')->count();
        return $failedJobs > 100 ? 'unhealthy' : 'healthy';
    }

    protected function getDatabaseHealth(): string
    {
        try {
            DB::select('SELECT 1');
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }
}
