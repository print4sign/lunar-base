<?php

namespace Lunar\Tests\Core\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;
use Lunar\Tests\Core\TestCase;

/**
 * @group models
 * @group supplier_orders
 */
class SupplierOrderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_be_cancelled_returns_true_when_pending()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'cancellation_deadline' => now()->addHours(24),
        ]);

        $this->assertTrue($supplierOrder->canBeCancelled());
    }

    /** @test */
    public function can_be_cancelled_returns_false_when_cancelled()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_CANCELLED,
        ]);

        $this->assertFalse($supplierOrder->canBeCancelled());
    }

    /** @test */
    public function can_be_cancelled_returns_false_when_delivered()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_DELIVERED,
        ]);

        $this->assertFalse($supplierOrder->canBeCancelled());
    }

    /** @test */
    public function can_be_cancelled_returns_false_when_past_deadline()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'cancellation_deadline' => now()->subHour(),
        ]);

        $this->assertFalse($supplierOrder->canBeCancelled());
    }

    /** @test */
    public function get_cancellation_fee_returns_zero_for_pending()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 10000,
        ]);

        $this->assertEquals(0, $supplierOrder->getCancellationFee());
    }

    /** @test */
    public function get_cancellation_fee_returns_zero_for_artwork_ready()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_ARTWORK_READY,
            'estimated_cost_price' => 10000,
        ]);

        $this->assertEquals(0, $supplierOrder->getCancellationFee());
    }

    /** @test */
    public function get_cancellation_fee_returns_10_percent_for_approved()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_APPROVED,
            'estimated_cost_price' => 10000, // €100
        ]);

        $this->assertEquals(1000, $supplierOrder->getCancellationFee()); // €10
    }

    /** @test */
    public function get_cancellation_fee_returns_10_percent_for_submitted()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_SUBMITTED,
            'estimated_cost_price' => 10000,
        ]);

        $this->assertEquals(1000, $supplierOrder->getCancellationFee());
    }

    /** @test */
    public function get_cancellation_fee_returns_50_percent_for_processing()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PROCESSING,
            'estimated_cost_price' => 10000, // €100
        ]);

        $this->assertEquals(5000, $supplierOrder->getCancellationFee()); // €50
    }

    /** @test */
    public function get_cancellation_fee_returns_full_price_for_shipped()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_SHIPPED,
            'estimated_cost_price' => 10000,
        ]);

        $this->assertEquals(10000, $supplierOrder->getCancellationFee());
    }

    /** @test */
    public function get_cancellation_time_remaining_returns_human_readable_string()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'cancellation_deadline' => now()->addHours(24),
        ]);

        $remaining = $supplierOrder->getCancellationTimeRemaining();
        $this->assertNotNull($remaining);
        $this->assertStringContainsString('hour', $remaining);
    }

    /** @test */
    public function get_cancellation_time_remaining_returns_null_when_cannot_cancel()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_DELIVERED,
        ]);

        $this->assertNull($supplierOrder->getCancellationTimeRemaining());
    }

    /** @test */
    public function get_cancellation_time_remaining_returns_null_when_no_deadline()
    {
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'cancellation_deadline' => null,
        ]);

        $this->assertNull($supplierOrder->getCancellationTimeRemaining());
    }
}
