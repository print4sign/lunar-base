<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'print_assets', function (Blueprint $table) {
            // Track Probo-hosted uploader sessions
            $table->string('probo_uploader_id')->nullable()->after('meta');
            $table->string('probo_uploader_status')->nullable()->after('probo_uploader_id');
            $table->timestamp('probo_uploader_confirmed_at')->nullable()->after('probo_uploader_status');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'print_assets', function (Blueprint $table) {
            $table->dropColumn([
                'probo_uploader_id',
                'probo_uploader_status',
                'probo_uploader_confirmed_at',
            ]);
        });
    }
};
