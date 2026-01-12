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
        Schema::create('sample_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('street');
            $table->string('house_number');
            $table->string('postal_code');
            $table->string('city');
            $table->string('country', 2)->default('NL');
            $table->string('email');
            $table->json('selected_samples');
            $table->enum('status', ['pending', 'processing', 'shipped', 'completed'])->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sample_requests');
    }
};
