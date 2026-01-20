<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocr_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_upload_id')->constrained()->onDelete('cascade');
            $table->string('field_name');
            $table->text('raw_text')->nullable();
            $table->text('parsed_value')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('document_upload_id');
            $table->index('field_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocr_results');
    }
};
