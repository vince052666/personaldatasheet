<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_console_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->index(); // Group related queries
            $table->string('query_type'); // analysis, validation, inconsistency_check, suggestion
            $table->text('prompt');
            $table->longText('response');
            $table->json('context')->nullable(); // PDS IDs, field names, etc.
            $table->json('findings')->nullable(); // Structured findings from AI
            $table->string('status')->default('pending'); // pending, reviewed, approved, rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->json('actions_taken')->nullable(); // What was done with the AI suggestion
            $table->boolean('flagged_for_review')->default(false);
            $table->timestamps();
            
            $table->index(['agency_id', 'session_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'flagged_for_review']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_console_logs');
    }
};
