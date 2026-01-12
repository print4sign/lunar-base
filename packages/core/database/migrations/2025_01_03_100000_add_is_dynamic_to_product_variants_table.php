<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->boolean('is_dynamic')->default(false)->after('supplier_product_id');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->dropColumn('is_dynamic');
        });
    }
};
