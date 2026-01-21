<?php

namespace App\Console\Commands;

use App\Services\EnhancedAuditLogService;
use Illuminate\Console\Command;

class VerifyAuditChain extends Command
{
    protected $signature = 'audit:verify {--start= : Start ID} {--end= : End ID}';
    protected $description = 'Verify audit log chain integrity';

    public function handle(EnhancedAuditLogService $auditService): int
    {
        $this->info('Verifying audit log chain...');

        try {
            $startId = $this->option('start');
            $endId = $this->option('end');

            $results = $auditService->verifyChain($startId, $endId);
            $tampering = array_filter($results, fn($r) => !$r['overall_valid']);

            if (empty($tampering)) {
                $this->info('✓ Audit chain is intact. No tampering detected.');
            } else {
                $this->error('✗ Tampering detected in ' . count($tampering) . ' record(s):');
                
                foreach ($tampering as $result) {
                    $this->warn("  - ID: {$result['id']}, Sequence: {$result['sequence']}");
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Verification failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
