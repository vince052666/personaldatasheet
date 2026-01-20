<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: We'll use encrypted casting in models for these fields
        // This migration documents which fields should be encrypted
        
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            // Add encryption marker comment for documentation
            $table->comment('Fields encrypted: tin, sss_no, pagibig_no, philhealth_no, residential_address, permanent_address');
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->comment('');
        });
    }
};
