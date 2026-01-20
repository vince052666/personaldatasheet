<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_data_sheet_id')->constrained()->onDelete('cascade');
            $table->date('from_date');
            $table->date('to_date')->nullable();
            $table->boolean('is_present')->default(false);
            $table->string('position_title');
            $table->string('department')->nullable();
            $table->string('company');
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->string('salary_grade')->nullable();
            $table->string('status_of_appointment')->nullable();
            $table->boolean('is_government_service')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_experiences');
    }
};
