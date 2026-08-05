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
        Schema::create('q_r_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('status')->default('pending');
            $table->timestamp('expired_at');
            $table->foreignId('patient_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('q_r_tokens');
    }
};
