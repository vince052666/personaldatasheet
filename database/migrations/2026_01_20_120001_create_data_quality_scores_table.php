<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_quality_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_data_sheet_id')->constrained()->onDelete('cascade');
            $table->decimal('completeness_score', 5, 2)->default(0);
            $table->decimal('accuracy_score', 5, 2)->default(0);
            $table->decimal('consistency_score', 5, 2)->default(0);
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->json('field_scores')->nullable();
            $table->json('issues')->nullable();
            $table->json('suggestions')->nullable();
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();
            
            $table->index('personal_data_sheet_id');
            $table->index('overall_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_quality_scores');
    }
};
