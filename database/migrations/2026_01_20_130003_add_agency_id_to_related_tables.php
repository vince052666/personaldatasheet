<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'document_uploads',
            'audit_logs',
            'activity_logs',
            'data_quality_scores',
            'recruitment',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('agency_id')->nullable()->after('id')->constrained()->onDelete('cascade');
                    $table->index('agency_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'document_uploads',
            'audit_logs',
            'activity_logs',
            'data_quality_scores',
            'recruitment',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['agency_id']);
                    $table->dropColumn('agency_id');
                });
            }
        }
    }
};
