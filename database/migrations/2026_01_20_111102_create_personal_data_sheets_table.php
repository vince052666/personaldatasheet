<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_data_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Personal Information
            $table->string('surname');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('name_extension')->nullable();
            $table->date('date_of_birth');
            $table->string('place_of_birth');
            $table->enum('sex', ['Male', 'Female']);
            $table->enum('civil_status', ['Single', 'Married', 'Widowed', 'Separated', 'Other']);
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('blood_type')->nullable();
            $table->string('gsis_id_no')->nullable();
            $table->string('pagibig_id_no')->nullable();
            $table->string('philhealth_no')->nullable();
            $table->string('sss_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('agency_employee_no')->nullable();
            $table->string('citizenship');
            $table->string('citizenship_type')->nullable();
            $table->string('country')->nullable();
            
            // Residential Address
            $table->string('residential_house_no')->nullable();
            $table->string('residential_street')->nullable();
            $table->string('residential_subdivision')->nullable();
            $table->string('residential_barangay')->nullable();
            $table->string('residential_city');
            $table->string('residential_province');
            $table->string('residential_zip_code')->nullable();
            
            // Permanent Address
            $table->string('permanent_house_no')->nullable();
            $table->string('permanent_street')->nullable();
            $table->string('permanent_subdivision')->nullable();
            $table->string('permanent_barangay')->nullable();
            $table->string('permanent_city');
            $table->string('permanent_province');
            $table->string('permanent_zip_code')->nullable();
            
            // Contact Information
            $table->string('telephone_no')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('email_address')->nullable();
            
            // Family Background
            $table->string('spouse_surname')->nullable();
            $table->string('spouse_first_name')->nullable();
            $table->string('spouse_middle_name')->nullable();
            $table->string('spouse_name_extension')->nullable();
            $table->string('spouse_occupation')->nullable();
            $table->string('spouse_employer')->nullable();
            $table->string('spouse_business_address')->nullable();
            $table->string('spouse_telephone_no')->nullable();
            
            $table->string('father_surname')->nullable();
            $table->string('father_first_name')->nullable();
            $table->string('father_middle_name')->nullable();
            $table->string('father_name_extension')->nullable();
            
            $table->string('mother_maiden_name')->nullable();
            $table->string('mother_surname')->nullable();
            $table->string('mother_first_name')->nullable();
            $table->string('mother_middle_name')->nullable();
            
            // Children (stored as JSON)
            $table->json('children')->nullable();
            
            // Questions
            $table->json('questions_answers')->nullable();
            
            // References
            $table->json('references')->nullable();
            
            // Government Issued ID
            $table->json('government_issued_id')->nullable();
            
            $table->boolean('is_current')->default(true);
            $table->integer('version')->default(1);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_data_sheets');
    }
};
