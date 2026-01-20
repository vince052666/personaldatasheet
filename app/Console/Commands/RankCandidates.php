<?php

namespace App\Console\Commands;

use App\Services\RankingService;
use App\Models\QualificationStandard;
use Illuminate\Console\Command;

class RankCandidates extends Command
{
    protected $signature = 'recruitment:rank {qualification_id : Qualification Standard ID}';
    protected $description = 'Rank candidates for a position';

    public function handle(RankingService $rankingService): int
    {
        $qualificationId = $this->argument('qualification_id');

        try {
            $standard = QualificationStandard::findOrFail($qualificationId);

            $this->info("Ranking candidates for: {$standard->position_title}");

            $rankings = $rankingService->rankCandidates($standard);

            $this->info("Ranked {$rankings->count()} candidate(s)");

            // Display top 10
            $this->table(
                ['Rank', 'Name', 'Total Score', 'Meets Requirements'],
                $rankings->take(10)->map(fn($r) => [
                    $r->rank,
                    $r->personalDataSheet->surname . ', ' . $r->personalDataSheet->first_name,
                    number_format($r->total_score, 2),
                    $r->meets_requirements ? 'Yes' : 'No',
                ])
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Ranking failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
