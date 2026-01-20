<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualification_standards', function (Blueprint $table) {
            $table->id();
            $table->string('position_title');
            $table->string('salary_grade')->nullable();
            $table->string('item_number')->nullable();
            $table->text('education_requirement')->nullable();
            $table->text('experience_requirement')->nullable();
            $table->text('training_requirement')->nullable();
            $table->text('eligibility_requirement')->nullable();
            $table->json('competency_requirements')->nullable();
            $table->integer('minimum_years_experience')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('position_title');
            $table->index('salary_grade');
        });

        Schema::create('position_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_standard_id')->constrained()->onDelete('cascade');
            $table->string('requirement_type'); // education, experience, training, eligibility
            $table->text('requirement_text');
            $table->integer('weight')->default(1); // For scoring
            $table->boolean('mandatory')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_data_sheet_id')->constrained()->onDelete('cascade');
            $table->foreignId('qualification_standard_id')->constrained()->onDelete('cascade');
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('education_score', 8, 2)->default(0);
            $table->decimal('experience_score', 8, 2)->default(0);
            $table->decimal('training_score', 8, 2)->default(0);
            $table->decimal('eligibility_score', 8, 2)->default(0);
            $table->integer('rank')->nullable();
            $table->boolean('meets_requirements')->default(false);
            $table->json('score_breakdown')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
            
            $table->index(['qualification_standard_id', 'rank']);
            $table->unique(['personal_data_sheet_id', 'qualification_standard_id']);
        });

        Schema::create('appointment_readiness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_data_sheet_id')->constrained()->onDelete('cascade');
            $table->foreignId('qualification_standard_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_ready')->default(false);
            $table->json('checklist')->nullable(); // Array of requirement statuses
            $table->json('missing_requirements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_readiness');
        Schema::dropIfExists('candidate_rankings');
        Schema::dropIfExists('position_requirements');
        Schema::dropIfExists('qualification_standards');
    }
};
