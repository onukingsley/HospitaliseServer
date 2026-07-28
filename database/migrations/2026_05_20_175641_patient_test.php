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
        Schema::create('patient_test', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rates_id')->constrained();
            $table->foreignId('lab_tests_id')->constrained();
            $table->string('remark')->nullable();
            $table->string('amount');
            $table->string('status')->default('undone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_test');
    }
};
