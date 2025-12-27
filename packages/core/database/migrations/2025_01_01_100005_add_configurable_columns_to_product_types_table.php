<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'product_types', function (Blueprint $table) {
            $table->boolean('configurable')->default(false)->after('name');
            $table->json('configurator_schema')->nullable()->after('configurable');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'product_types', function (Blueprint $table) {
            $table->dropColumn(['configurable', 'configurator_schema']);
        });
    }
};
