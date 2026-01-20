<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->string('status')->default('draft')->after('version');
            $table->string('department')->nullable()->after('status');
            $table->string('position')->nullable()->after('department');
            $table->timestamp('reviewed_at')->nullable()->after('position');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete()->after('reviewed_at');
            $table->text('review_notes')->nullable()->after('reviewed_by');
            
            $table->index('status');
            $table->index('department');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::table('personal_data_sheets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['department']);
            $table->dropIndex(['position']);
            
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['status', 'department', 'position', 'reviewed_at', 'reviewed_by', 'review_notes']);
        });
    }
};
