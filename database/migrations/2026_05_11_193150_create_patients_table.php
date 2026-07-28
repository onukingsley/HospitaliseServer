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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('blood_group');
            $table->string('genotype');
            $table->string('allergies')->nullable();
            $table->string('nos_name')->nullable();
            $table->string('nos_address')->nullable();
            $table->string('nos_phone_no')->nullable();
            $table->string('insurance_id')->nullable();
            $table->string('insurance_provider')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
