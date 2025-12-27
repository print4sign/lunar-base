<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('handle')->unique()->index();
            $table->string('driver');
            $table->json('credentials')->nullable();
            $table->json('capabilities')->nullable();
            $table->boolean('enabled')->default(false)->index();
            $table->integer('priority')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'suppliers');
    }
};
