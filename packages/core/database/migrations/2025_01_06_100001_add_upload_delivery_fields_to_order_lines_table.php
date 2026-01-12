<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'order_lines', function (Blueprint $table) {
            // Track deliver-later uploads
            $table->boolean('upload_deliver_later')->default(false)->after('meta');
            $table->string('upload_token')->nullable()->unique()->after('upload_deliver_later');
            $table->timestamp('upload_token_expires_at')->nullable()->after('upload_token');
            $table->timestamp('upload_completed_at')->nullable()->after('upload_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'order_lines', function (Blueprint $table) {
            $table->dropColumn([
                'upload_deliver_later',
                'upload_token',
                'upload_token_expires_at',
                'upload_completed_at',
            ]);
        });
    }
};
