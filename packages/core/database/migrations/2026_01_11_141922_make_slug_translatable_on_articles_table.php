<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert existing slug strings to JSON format
        $articles = DB::table('lunar_articles')->get(['id', 'slug']);

        foreach ($articles as $article) {
            DB::table('lunar_articles')
                ->where('id', $article->id)
                ->update([
                    'slug' => json_encode(['nl' => $article->slug]),
                ]);
        }

        // Change column type from string to json
        Schema::table('lunar_articles', function (Blueprint $table) {
            $table->json('slug')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get current JSON slugs and extract 'nl' value
        $articles = DB::table('lunar_articles')->get(['id', 'slug']);

        foreach ($articles as $article) {
            $slugData = json_decode($article->slug, true);
            $slug = $slugData['nl'] ?? $slugData['en'] ?? 'article-'.$article->id;

            DB::table('lunar_articles')
                ->where('id', $article->id)
                ->update([
                    'slug' => $slug,
                ]);
        }

        Schema::table('lunar_articles', function (Blueprint $table) {
            $table->string('slug')->change();
        });
    }
};
