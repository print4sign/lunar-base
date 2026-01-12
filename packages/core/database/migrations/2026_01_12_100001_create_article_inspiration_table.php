<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'article_inspiration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained($this->prefix.'articles')
                ->cascadeOnDelete();
            $table->foreignId('inspiration_id')
                ->constrained($this->prefix.'inspirations')
                ->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['article_id', 'inspiration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'article_inspiration');
    }
};
