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
        Schema::create('educational_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personal_data_sheet_id')->constrained()->onDelete('cascade');
            $table->enum('level', ['Elementary', 'Secondary', 'Vocational', 'College', 'Graduate Studies']);
            $table->string('school_name');
            $table->string('degree_course')->nullable();
            $table->year('year_from')->nullable();
            $table->year('year_to')->nullable();
            $table->string('units_earned')->nullable();
            $table->year('year_graduated')->nullable();
            $table->string('scholarship')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educational_backgrounds');
    }
};
