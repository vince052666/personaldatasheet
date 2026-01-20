<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('signature', 128)->nullable()->after('metadata');
            $table->string('previous_signature', 128)->nullable()->after('signature');
            $table->unsignedBigInteger('chain_sequence')->default(0)->after('previous_signature');
            $table->string('chain_hash', 64)->nullable()->after('chain_sequence');
            
            $table->index('chain_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['signature', 'previous_signature', 'chain_sequence', 'chain_hash']);
        });
    }
};
