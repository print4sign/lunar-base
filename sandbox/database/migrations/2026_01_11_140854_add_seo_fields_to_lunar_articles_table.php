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
        Schema::table('lunar_articles', function (Blueprint $table) {
            $table->json('meta_title')->nullable()->after('meta_description');
            $table->json('meta_keywords')->nullable()->after('meta_title');
            $table->string('og_image')->nullable()->after('meta_keywords');
            $table->string('canonical_url')->nullable()->after('og_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lunar_articles', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_keywords', 'og_image', 'canonical_url']);
        });
    }
};
