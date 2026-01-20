<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;

class CreateAgency extends Command
{
    protected $signature = 'agency:create 
                            {code : Agency code (e.g., DILG, DOH)} 
                            {name : Full agency name}
                            {--email= : Contact email}
                            {--phone= : Contact phone}';

    protected $description = 'Create a new government agency';

    public function handle()
    {
        $code = strtoupper($this->argument('code'));
        $name = $this->argument('name');

        if (Agency::where('code', $code)->exists()) {
            $this->error("Agency with code '{$code}' already exists!");
            return 1;
        }

        $agency = Agency::create([
            'code' => $code,
            'name' => $name,
            'contact_email' => $this->option('email'),
            'contact_phone' => $this->option('phone'),
            'is_active' => true,
        ]);

        $this->info("Agency '{$name}' created successfully!");
        $this->table(
            ['ID', 'Code', 'Name', 'Email', 'Phone'],
            [[$agency->id, $agency->code, $agency->name, $agency->contact_email, $agency->contact_phone]]
        );

        return 0;
    }
}
