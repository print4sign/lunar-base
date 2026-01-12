<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lunar_supplier_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained('lunar_supplier_orders');
            $table->string('type')->comment('quality, damage, missing, wrong_product, delay');
            $table->string('severity')->default('medium')->comment('low, medium, high, critical');
            $table->text('description');
            $table->json('images')->nullable();
            $table->string('status')->default('reported')->comment('reported, acknowledged, resolved, rejected');
            $table->string('resolution')->nullable()->comment('refund, reprint, credit, replacement');
            $table->integer('compensation_amount')->nullable()->comment('in cents');
            $table->foreignId('reported_by')->constrained('users');
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('reported_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->timestamps();

            $table->index(['supplier_order_id', 'status']);
            $table->index(['type', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lunar_supplier_issues');
    }
};
