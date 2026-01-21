<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_staging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->integer('row_number');
            $table->json('raw_data'); // Original data from import
            $table->json('mapped_data')->nullable(); // Data after mapping/transformation
            $table->string('status')->default('pending'); // pending, validated, imported, error, duplicate
            $table->string('duplicate_key')->nullable(); // For deduplication (email, employee_id, etc.)
            $table->foreignId('matched_pds_id')->nullable()->constrained('personal_data_sheets')->onDelete('set null');
            $table->text('validation_errors')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['import_batch_id', 'status']);
            $table->index(['agency_id', 'duplicate_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_staging');
    }
};
