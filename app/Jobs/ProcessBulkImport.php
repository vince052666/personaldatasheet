<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ExcelImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessBulkImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    public function __construct(
        public string $filePath,
        public User $user
    ) {}

    public function handle(ExcelImportService $importService): void
    {
        try {
            $fullPath = Storage::path($this->filePath);
            
            if (!file_exists($fullPath)) {
                throw new \Exception('Import file not found');
            }
            
            $result = $importService->importFromExcel($fullPath, $this->user);
            
            Storage::delete($this->filePath);
            
        } catch (\Exception $e) {
            \Log::error('Bulk import failed', [
                'file' => $this->filePath,
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->fail($e);
        }
    }
}
