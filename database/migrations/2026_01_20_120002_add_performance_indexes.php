<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('is_current');
            $table->index('created_at');
            $table->index(['surname', 'first_name']);
            $table->fullText(['surname', 'first_name', 'middle_name', 'email_address']);
        });

        Schema::table('work_experiences', function (Blueprint $table) {
            $table->index('personal_data_sheet_id');
            $table->index(['date_from', 'date_to']);
        });

        Schema::table('educational_backgrounds', function (Blueprint $table) {
            $table->index('personal_data_sheet_id');
            $table->index('level');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('auditable_type');
            $table->index('auditable_id');
            $table->index('user_id');
            $table->index('created_at');
        });

        Schema::table('document_uploads', function (Blueprint $table) {
            $table->index('personal_data_sheet_id');
            $table->index('document_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_current']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['surname', 'first_name']);
            $table->dropFullText(['surname', 'first_name', 'middle_name', 'email_address']);
        });

        Schema::table('work_experiences', function (Blueprint $table) {
            $table->dropIndex(['personal_data_sheet_id']);
            $table->dropIndex(['date_from', 'date_to']);
        });

        Schema::table('educational_backgrounds', function (Blueprint $table) {
            $table->dropIndex(['personal_data_sheet_id']);
            $table->dropIndex(['level']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['auditable_type']);
            $table->dropIndex(['auditable_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('document_uploads', function (Blueprint $table) {
            $table->dropIndex(['personal_data_sheet_id']);
            $table->dropIndex(['document_type']);
            $table->dropIndex(['created_at']);
        });
    }
};
