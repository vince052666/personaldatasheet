<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            $table->string('processing_status')->default('pending')->after('status');
            $table->timestamp('processed_at')->nullable()->after('processing_status');
        });
    }

    public function down(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            $table->dropColumn(['processing_status', 'processed_at']);
        });
    }
};
