<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->foreignId('supplier_product_id')->nullable()->after('product_id')
                ->constrained($this->prefix.'supplier_products')->nullOnDelete();
            $table->json('configuration')->nullable()->after('attribute_data');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            if ($this->canDropForeignKeys()) {
                $table->dropForeign(['supplier_product_id']);
            }
            $table->dropColumn(['supplier_product_id', 'configuration']);
        });
    }
};
