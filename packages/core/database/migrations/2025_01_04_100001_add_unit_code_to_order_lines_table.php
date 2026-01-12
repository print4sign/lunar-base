<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'order_lines', function (Blueprint $table) {
            $table->string('unit_code', 10)->default('pc')->after('unit_quantity')->index();
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'order_lines', function (Blueprint $table) {
            $table->dropIndex(['unit_code']);
            $table->dropColumn('unit_code');
        });
    }
};
