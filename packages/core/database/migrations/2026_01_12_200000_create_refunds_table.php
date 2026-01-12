<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lunar_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('lunar_orders');
            $table->foreignId('supplier_order_id')->nullable()->constrained('lunar_supplier_orders');
            $table->string('payment_intent_id')->nullable();
            $table->string('refund_id')->nullable()->comment('External refund ID from payment provider');
            $table->integer('amount')->comment('in cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('method')->default('original')->comment('original, store_credit, manual');
            $table->string('status')->default('pending')->comment('pending, processing, completed, failed');
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('refund_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunar_refunds');
    }
};
