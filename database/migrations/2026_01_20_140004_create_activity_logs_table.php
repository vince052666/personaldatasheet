<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('activity_type'); // login, logout, failed_login, access, export, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();
            
            $table->index(['user_id', 'activity_type']);
            $table->index('performed_at');
            $table->index('ip_address');
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // suspicious_login, brute_force, unauthorized_access
            $table->string('severity'); // low, medium, high, critical
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->boolean('resolved')->default(false);
            $table->timestamp('detected_at');
            $table->timestamps();
            
            $table->index(['severity', 'resolved']);
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('activity_logs');
    }
};
