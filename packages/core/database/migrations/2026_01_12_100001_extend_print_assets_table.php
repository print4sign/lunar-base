<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'print_assets', function (Blueprint $table) {
            // Supplier relationship
            $table->foreignId('supplier_order_id')
                ->nullable()
                ->after('order_line_id')
                ->constrained($this->prefix.'supplier_orders')
                ->nullOnDelete();

            // Supplier file tracking
            $table->string('supplier_file_id')->nullable()->after('supplier_order_id');
            $table->string('supplier_status')->nullable()->after('supplier_file_id');
            $table->json('validation_errors')->nullable()->after('supplier_status');

            // Index for supplier relationships
            $table->index('supplier_order_id');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'print_assets', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['supplier_order_id']);

            // Drop index
            $table->dropIndex(['supplier_order_id']);

            // Drop columns
            $table->dropColumn([
                'validation_errors',
                'supplier_status',
                'supplier_file_id',
                'supplier_order_id',
            ]);
        });
    }
};
