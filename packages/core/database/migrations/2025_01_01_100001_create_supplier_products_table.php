<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'supplier_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained($this->prefix.'suppliers')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained($this->prefix.'products')->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained($this->prefix.'product_variants')->nullOnDelete();
            $table->string('external_id')->index();
            $table->string('external_name')->nullable();
            $table->json('external_data')->nullable();
            $table->json('configurator_schema')->nullable();
            $table->boolean('synced')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'supplier_products');
    }
};
