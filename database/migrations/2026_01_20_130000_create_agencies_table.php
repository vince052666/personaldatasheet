<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 'DILG', 'DOH', 'DepEd'
            $table->string('name'); // Full agency name
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('settings')->nullable(); // Agency-specific settings
            $table->json('branding')->nullable(); // Colors, fonts, etc.
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Add default super-agency for cross-agency super admins
        DB::table('agencies')->insert([
            'code' => 'SUPER',
            'name' => 'Super Admin Agency',
            'description' => 'Cross-agency administration',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
