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
        Schema::create('subject_allotments', function (Blueprint $table) {
            $table->bigIncrements('subject_id');
            $table->Interger('sm_id');
            $table->Interger('class_id');
            $table->Interger('section_id');
            $table->Interger('teacher_id');
            $table->string('academic_yr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_allotments');
    }
};
