<?php

namespace App\Jobs;

use App\Models\PersonalDataSheet;
use App\Services\AIAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeDataQuality implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PersonalDataSheet $personalDataSheet
    ) {}

    public function handle(AIAnalysisService $aiAnalysisService): void
    {
        $aiAnalysisService->analyzeDataQuality($this->personalDataSheet);
    }
}
