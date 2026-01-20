<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_data_sheet_id')->constrained()->onDelete('cascade');
            $table->string('workflow_type')->default('pds_approval');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'assigned_to']);
            $table->index('workflow_type');
        });

        Schema::create('approval_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained()->onDelete('cascade');
            $table->string('action'); // submitted, approved, rejected, reassigned
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_history');
        Schema::dropIfExists('approval_workflows');
    }
};
