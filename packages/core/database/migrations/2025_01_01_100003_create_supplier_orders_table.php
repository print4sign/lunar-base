<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained($this->prefix.'orders')->cascadeOnDelete();
            $table->foreignId('order_line_id')->constrained($this->prefix.'order_lines')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained($this->prefix.'suppliers');
            $table->string('external_order_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->json('external_data')->nullable();
            $table->json('tracking')->nullable();
            $table->unsignedBigInteger('cost_price')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'supplier_orders');
    }
};
