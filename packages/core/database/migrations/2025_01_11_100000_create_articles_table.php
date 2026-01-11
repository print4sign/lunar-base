<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'articles', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('excerpt')->nullable();
            $table->json('body');
            $table->json('meta_description')->nullable();
            $table->string('featured_image')->nullable();
            $table->string('category'); // klantenservice, blog
            $table->string('subcategory')->nullable();
            $table->json('tags')->nullable();
            $table->string('source_url')->nullable();
            $table->json('original_content')->nullable();
            $table->string('status')->default('draft'); // draft, rewritten, published
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'articles');
    }
};
