<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_locks', function (Blueprint $table) {
            $table->id();
            $table->string('lockable_type');
            $table->unsignedBigInteger('lockable_id');
            $table->foreignId('locked_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('locked_at');
            $table->timestamp('expires_at');
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index(['lockable_type', 'lockable_id']);
            $table->index('expires_at');
            $table->unique(['lockable_type', 'lockable_id']);
        });

        // Add version number for optimistic locking
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->unsignedBigInteger('version')->default(1)->after('id');
            $table->index('version');
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->dropColumn('version');
        });
        Schema::dropIfExists('record_locks');
    }
};
