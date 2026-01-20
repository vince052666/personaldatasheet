<?php

namespace App\Console\Commands;

use App\Services\PermissionService;
use Illuminate\Console\Command;

class SetupPermissions extends Command
{
    protected $signature = 'permissions:setup';
    protected $description = 'Setup default permissions';

    public function handle(PermissionService $permissionService): int
    {
        $this->info('Setting up default permissions...');

        try {
            $permissionService->setupDefaultPermissions();

            $this->info('Default permissions created successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to setup permissions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
