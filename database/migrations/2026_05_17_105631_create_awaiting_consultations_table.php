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
        Schema::create('awaiting_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rates_id')->nullable()->constrained();
            $table->foreignId('payment_id')->nullable()->constrained();
            $table->foreignId('diagnosis_id')->nullable()->constrained();
            $table->string('payment_status')->default('unpaid'); //paid or unpaid
            $table->string('amount')->nullable();
            $table->string('attendance_status')->default('unseen'); //seen or unseen
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('awaiting_consultations');
    }
};
