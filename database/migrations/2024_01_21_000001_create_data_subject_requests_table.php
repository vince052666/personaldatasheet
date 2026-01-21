<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personal_data_sheet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('request_type'); // access, rectification, erasure, portability
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_id_number')->nullable();
            $table->text('request_details');
            $table->string('status')->default('pending'); // pending, processing, completed, rejected
            $table->text('response_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agency_id', 'status']);
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
    }
};
