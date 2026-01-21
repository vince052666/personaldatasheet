<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type'); // pds, audit_log, document
            $table->integer('retention_years')->default(7);
            $table->boolean('auto_archive')->default(true);
            $table->boolean('auto_delete')->default(false);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique('resource_type');
        });

        Schema::create('archived_records', function (Blueprint $table) {
            $table->id();
            $table->string('archivable_type');
            $table->unsignedBigInteger('archivable_id');
            $table->timestamp('archived_at');
            $table->timestamp('delete_after')->nullable();
            $table->text('archive_reason')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('original_data')->nullable();
            $table->timestamps();
            
            $table->index(['archivable_type', 'archivable_id']);
            $table->index('delete_after');
        });

        // Add deleted_at for soft deletes on PDS
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::dropIfExists('archived_records');
        Schema::dropIfExists('data_retention_policies');
    }
};
