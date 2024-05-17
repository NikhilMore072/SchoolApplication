<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('house')->nullable();
            $table->string('admitted_in_class')->nullable();
            $table->string('gender', 10);
            $table->string('blood_group', 5)->nullable();
            $table->string('nationality')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('emergency_name')->nullable();
            $table->date('date_of_birth');
            $table->date('date_of_admission');
            $table->string('grn_no')->nullable();
            $table->string('student_id_no')->nullable();
            $table->string('student_aadhaar_no')->nullable();
            $table->string('class')->nullable();
            $table->string('division')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->text('emergency_address')->nullable();
            $table->string('emergency_contact', 20)->nullable();
            $table->string('transport_mode')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('allergies')->nullable();
            $table->float('height')->nullable();
            $table->string('roll_no')->nullable();
            $table->string('category')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->float('weight')->nullable();
            $table->boolean('has_spectacles')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
