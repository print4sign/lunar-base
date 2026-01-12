<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'supplier_orders', function (Blueprint $table) {
            // Cancellation fields
            $table->boolean('cancellable')->default(true)->after('status');
            $table->timestamp('cancellation_deadline')->nullable()->after('cancellable');
            $table->timestamp('cancellation_requested_at')->nullable()->after('cancellation_deadline');
            $table->foreignId('cancellation_requested_by')->nullable()->constrained('users')->nullOnDelete()->after('cancellation_requested_at');
            $table->text('cancellation_reason')->nullable()->after('cancellation_requested_by');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete()->after('cancelled_at');
            $table->unsignedBigInteger('cancellation_fee')->nullable()->comment('In cents')->after('cancelled_by');

            // Cost tracking fields
            $table->unsignedBigInteger('estimated_cost_price')->nullable()->comment('In cents')->after('cost_price');
            $table->unsignedBigInteger('actual_cost_price')->nullable()->comment('In cents')->after('estimated_cost_price');
            $table->unsignedBigInteger('supplier_shipping_cost')->nullable()->comment('In cents')->after('actual_cost_price');
            $table->json('supplier_additional_costs')->nullable()->after('supplier_shipping_cost');
            $table->unsignedBigInteger('supplier_total_cost')->nullable()->comment('In cents')->after('supplier_additional_costs');

            // Revenue tracking fields
            $table->unsignedBigInteger('order_line_unit_price')->nullable()->comment('In cents')->after('supplier_total_cost');
            $table->unsignedBigInteger('order_line_total')->nullable()->comment('In cents')->after('order_line_unit_price');
            $table->bigInteger('profit_margin_cents')->nullable()->comment('In cents')->after('order_line_total');
            $table->decimal('profit_margin_percentage', 5, 2)->nullable()->after('profit_margin_cents');

            // Refund tracking fields
            $table->unsignedBigInteger('refund_amount')->nullable()->comment('In cents')->after('profit_margin_percentage');
            $table->timestamp('refund_issued_at')->nullable()->after('refund_amount');
            $table->text('refund_reason')->nullable()->after('refund_issued_at');

            // Approval workflow fields
            $table->boolean('requires_approval')->default(true)->after('refund_reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete()->after('requires_approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->text('rejected_reason')->nullable()->after('approval_notes');

            // Artwork management fields
            $table->json('artwork_files')->nullable()->after('rejected_reason');
            $table->enum('artwork_status', [
                'not_required',
                'pending',
                'uploaded',
                'approved',
                'rejected',
            ])->default('not_required')->after('artwork_files');
            $table->timestamp('artwork_approval_deadline')->nullable()->after('artwork_status');

            // Delivery tracking fields
            $table->date('estimated_delivery_date')->nullable()->after('artwork_approval_deadline');
            $table->date('actual_delivery_date')->nullable()->after('estimated_delivery_date');
            $table->json('tracking_numbers')->nullable()->after('actual_delivery_date');
            $table->string('tracking_url')->nullable()->after('tracking_numbers');

            // External status fields
            $table->string('substatus')->nullable()->after('status');
            $table->string('external_status')->nullable()->after('substatus');

            // Indexes for common queries
            $table->index(['status', 'requires_approval']);
            $table->index(['supplier_id', 'status']);
            $table->index(['order_id', 'status']);
            $table->index('cancellation_deadline');
            $table->index('estimated_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'supplier_orders', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['cancellation_requested_by']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['approved_by']);

            // Drop indexes
            $table->dropIndex(['status', 'requires_approval']);
            $table->dropIndex(['supplier_id', 'status']);
            $table->dropIndex(['order_id', 'status']);
            $table->dropIndex(['cancellation_deadline']);
            $table->dropIndex(['estimated_delivery_date']);

            // Drop columns (in reverse order)
            $table->dropColumn([
                'external_status',
                'substatus',
                'tracking_url',
                'tracking_numbers',
                'actual_delivery_date',
                'estimated_delivery_date',
                'artwork_approval_deadline',
                'artwork_status',
                'artwork_files',
                'rejected_reason',
                'approval_notes',
                'approved_at',
                'approved_by',
                'requires_approval',
                'refund_reason',
                'refund_issued_at',
                'refund_amount',
                'profit_margin_percentage',
                'profit_margin_cents',
                'order_line_total',
                'order_line_unit_price',
                'supplier_total_cost',
                'supplier_additional_costs',
                'supplier_shipping_cost',
                'actual_cost_price',
                'estimated_cost_price',
                'cancellation_fee',
                'cancelled_by',
                'cancelled_at',
                'cancellation_reason',
                'cancellation_requested_by',
                'cancellation_requested_at',
                'cancellation_deadline',
                'cancellable',
            ]);
        });
    }
};
