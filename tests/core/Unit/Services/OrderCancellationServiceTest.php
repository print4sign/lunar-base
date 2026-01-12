<?php

namespace Lunar\Tests\Core\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;
use Lunar\Models\SupplierProduct;
use Lunar\Services\OrderCancellationService;
use Lunar\Tests\Core\TestCase;

/**
 * @group orders
 * @group cancellation
 */
class OrderCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderCancellationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(OrderCancellationService::class);
    }

    /** @test */
    public function it_cancels_a_supplier_order_with_no_fee_when_pending()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 10000, // €100
            'order_line_total' => 15000, // €150
            'cancellation_deadline' => now()->addHours(24),
        ]);

        $result = $this->service->cancelSupplierOrder(
            $supplierOrder,
            $user,
            'Customer requested cancellation',
            true
        );

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->cancellationFee);
        $this->assertEquals(SupplierOrder::STATUS_CANCELLED, $supplierOrder->fresh()->status);
    }

    /** @test */
    public function it_applies_10_percent_fee_when_submitted()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_SUBMITTED,
            'estimated_cost_price' => 10000, // €100
            'order_line_total' => 15000, // €150
            'cancellation_deadline' => now()->addHours(24),
        ]);

        $result = $this->service->cancelSupplierOrder(
            $supplierOrder,
            $user,
            'Customer changed mind',
            false
        );

        $this->assertTrue($result->success);
        $this->assertEquals(1000, $result->cancellationFee); // 10% of €100 = €10
        $this->assertEquals(SupplierOrder::STATUS_CANCELLED, $supplierOrder->fresh()->status);
    }

    /** @test */
    public function it_applies_50_percent_fee_when_processing()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PROCESSING,
            'estimated_cost_price' => 10000, // €100
            'order_line_total' => 15000, // €150
            'cancellation_deadline' => now()->addHours(24),
        ]);

        $result = $this->service->cancelSupplierOrder(
            $supplierOrder,
            $user,
            'Customer emergency',
            false
        );

        $this->assertTrue($result->success);
        $this->assertEquals(5000, $result->cancellationFee); // 50% of €100 = €50
    }

    /** @test */
    public function it_cannot_cancel_when_already_cancelled()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_CANCELLED,
            'estimated_cost_price' => 10000,
            'order_line_total' => 15000,
        ]);

        $result = $this->service->cancelSupplierOrder(
            $supplierOrder,
            $user,
            'Test',
            false
        );

        $this->assertFalse($result->success);
        $this->assertStringContainsString('cannot be cancelled', $result->error);
    }

    /** @test */
    public function it_cannot_cancel_when_delivered()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_DELIVERED,
            'estimated_cost_price' => 10000,
            'order_line_total' => 15000,
        ]);

        $result = $this->service->cancelSupplierOrder(
            $supplierOrder,
            $user,
            'Test',
            false
        );

        $this->assertFalse($result->success);
    }

    /** @test */
    public function it_cannot_cancel_when_past_deadline()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();
        $orderLine = OrderLine::factory()->create(['order_id' => $order->id]);

        $supplierOrder = SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine->id,
            'supplier_id' => $supplier->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 10000,
            'order_line_total' => 15000,
            'cancellation_deadline' => now()->subHour(), // Past deadline
        ]);

        $result = $this->service->cancelSupplierOrder(
            $supplierOrder,
            $user,
            'Test',
            false
        );

        $this->assertFalse($result->success);
    }

    /** @test */
    public function it_cancels_full_order_with_multiple_supplier_orders()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier1 = Supplier::factory()->create(['driver' => 'internal']);
        $supplier2 = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();

        $orderLine1 = OrderLine::factory()->create(['order_id' => $order->id]);
        $orderLine2 = OrderLine::factory()->create(['order_id' => $order->id]);

        SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine1->id,
            'supplier_id' => $supplier1->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 5000,
            'order_line_total' => 8000,
            'cancellation_deadline' => now()->addHours(24),
        ]);

        SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine2->id,
            'supplier_id' => $supplier2->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 7000,
            'order_line_total' => 10000,
            'cancellation_deadline' => now()->addHours(24),
        ]);

        $result = $this->service->cancelOrder(
            $order,
            $user,
            'Full order cancellation',
            false
        );

        $this->assertTrue($result->success);
        $this->assertCount(2, $result->cancelled);
        $this->assertCount(0, $result->failed);
        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    /** @test */
    public function it_updates_order_status_to_partially_cancelled_when_some_fail()
    {
        $user = \Lunar\Tests\Core\Stubs\User::factory()->create();
        $supplier1 = Supplier::factory()->create(['driver' => 'internal']);
        $supplier2 = Supplier::factory()->create(['driver' => 'internal']);
        $order = Order::factory()->create();

        $orderLine1 = OrderLine::factory()->create(['order_id' => $order->id]);
        $orderLine2 = OrderLine::factory()->create(['order_id' => $order->id]);

        // This one can be cancelled
        SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine1->id,
            'supplier_id' => $supplier1->id,
            'status' => SupplierOrder::STATUS_PENDING,
            'estimated_cost_price' => 5000,
            'order_line_total' => 8000,
            'cancellation_deadline' => now()->addHours(24),
        ]);

        // This one cannot (already delivered)
        SupplierOrder::create([
            'order_id' => $order->id,
            'order_line_id' => $orderLine2->id,
            'supplier_id' => $supplier2->id,
            'status' => SupplierOrder::STATUS_DELIVERED,
            'estimated_cost_price' => 7000,
            'order_line_total' => 10000,
        ]);

        $result = $this->service->cancelOrder(
            $order,
            $user,
            'Partial cancellation',
            false
        );

        $this->assertFalse($result->success);
        $this->assertCount(1, $result->cancelled);
        $this->assertCount(1, $result->failed);
        $this->assertEquals('partially_cancelled', $order->fresh()->status);
    }
}
