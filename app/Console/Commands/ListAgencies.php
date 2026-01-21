<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;

class ListAgencies extends Command
{
    protected $signature = 'agency:list {--active : Show only active agencies}';

    protected $description = 'List all government agencies';

    public function handle()
    {
        $query = Agency::query();

        if ($this->option('active')) {
            $query->where('is_active', true);
        }

        $agencies = $query->get();

        if ($agencies->isEmpty()) {
            $this->warn('No agencies found.');
            return 0;
        }

        $this->table(
            ['ID', 'Code', 'Name', 'Users', 'PDS Records', 'Active'],
            $agencies->map(function ($agency) {
                return [
                    $agency->id,
                    $agency->code,
                    $agency->name,
                    $agency->users()->count(),
                    $agency->personalDataSheets()->count(),
                    $agency->is_active ? 'Yes' : 'No',
                ];
            })
        );

        return 0;
    }
}
