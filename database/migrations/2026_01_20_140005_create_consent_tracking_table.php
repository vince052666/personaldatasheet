<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('consent_type'); // data_processing, data_sharing, privacy_policy
            $table->boolean('consented')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('version')->default('1.0'); // Policy version
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'consent_type']);
        });

        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('request_type'); // access, rectification, erasure, portability
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->text('description')->nullable();
            $table->text('response')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('data_consents');
    }
};
