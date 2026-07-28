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
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_report_id')->nullable()->constrained();
            $table->foreignId('diagnosis_id')->nullable()->constrained();
            $table->string('lab_test_name');
            $table->string('lab_test_description');
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->nullable()->constrained();
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->string('lab_test_amount');
            $table->longText('lab_test_result')->nullable();
            $table->string('lab_test_result_image')->nullable();
            $table->string('lab_test_payment_status')->default('unpaid');
            $table->string('lab_test_progress_status')->default('undone'); //undone, pending, completed
            $table->foreignId('lab_scientist_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
