<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'product_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_name');
            $table->string('canonical_handle')->unique();
            $table->json('attributes')->nullable();
            $table->timestamps();
        });

        Schema::create($this->prefix.'product_mapping_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_mapping_id')->constrained($this->prefix.'product_mappings')->cascadeOnDelete();
            $table->foreignId('supplier_product_id')->constrained($this->prefix.'supplier_products')->cascadeOnDelete();
            $table->decimal('confidence_score', 5, 4)->default(0);
            $table->string('status')->default('pending');
            $table->foreignId('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['product_mapping_id', 'supplier_product_id'], 'product_mapping_supplier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'product_mapping_suppliers');
        Schema::dropIfExists($this->prefix.'product_mappings');
    }
};
