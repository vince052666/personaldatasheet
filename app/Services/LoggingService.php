<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoggingService
{
    protected string $correlationId;

    public function __construct()
    {
        $this->correlationId = request()->header('X-Correlation-ID') 
            ?? request()->get('correlation_id')
            ?? Str::uuid()->toString();
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $enrichedContext = array_merge($context, [
            'correlation_id' => $this->correlationId,
            'agency_id' => auth()->user()?->agency_id,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        $channel = $this->getChannelForAgency();

        Log::channel($channel)->$level($message, $enrichedContext);
    }

    protected function getChannelForAgency(): string
    {
        $agencyId = auth()->user()?->agency_id;

        if (!$agencyId) {
            return 'stack';
        }

        return "agency-{$agencyId}";
    }

    public function auditLog(string $action, string $model, $modelId, array $changes = []): void
    {
        $this->info("Audit: {$action} on {$model}", [
            'audit_action' => $action,
            'audit_model' => $model,
            'audit_model_id' => $modelId,
            'audit_changes' => $changes,
            'audit_user' => auth()->user()?->name,
        ]);
    }

    public function performanceLog(string $operation, float $duration, array $metadata = []): void
    {
        $this->info("Performance: {$operation}", array_merge([
            'performance_operation' => $operation,
            'performance_duration_ms' => round($duration * 1000, 2),
            'performance_threshold_exceeded' => $duration > 1.0,
        ], $metadata));
    }

    public function securityLog(string $event, string $severity, array $details = []): void
    {
        $this->log($severity, "Security: {$event}", array_merge([
            'security_event' => $event,
            'security_severity' => $severity,
        ], $details));
    }
}
