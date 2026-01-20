<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('import_staging_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->integer('row_number')->nullable();
            $table->string('error_type'); // validation, duplicate, system, data_quality
            $table->string('field')->nullable();
            $table->text('error_message');
            $table->json('error_context')->nullable(); // Additional context about the error
            $table->string('severity')->default('error'); // warning, error, critical
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['import_batch_id', 'error_type']);
            $table->index(['agency_id', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
