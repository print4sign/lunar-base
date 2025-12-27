<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'prices', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_price')->nullable()->after('compare_price');
            $table->foreignId('supplier_id')->nullable()->after('cost_price')
                ->constrained($this->prefix.'suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'prices', function (Blueprint $table) {
            if ($this->canDropForeignKeys()) {
                $table->dropForeign(['supplier_id']);
            }
            $table->dropColumn(['cost_price', 'supplier_id']);
        });
    }
};
