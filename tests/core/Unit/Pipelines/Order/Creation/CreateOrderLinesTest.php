<?php

namespace Lunar\Tests\Unit\Pipelines\Order\Creation;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\DataTransferObjects\SupplierSelectionResult;
use Lunar\Exceptions\Suppliers\NoSuppliersAvailableException;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Currency;
use Lunar\Models\Order;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;
use Lunar\Models\SupplierProduct;
use Lunar\Pipelines\Order\Creation\CreateOrderLines;
use Lunar\Services\RuntimeSupplierSelector;
use Lunar\Tests\Core\TestCase;

/**
 * @group pipelines
 * @group order-creation
 * @group supplier-selection
 */
class CreateOrderLinesTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected Supplier $supplierA;

    protected Supplier $supplierB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::factory()->create([
            'code' => 'EUR',
            'default' => true,
        ]);

        $this->supplierA = Supplier::factory()->create([
            'name' => 'Supplier A',
            'enabled' => true,
            'capabilities' => [
                'cancellation_window_hours' => 24,
            ],
        ]);

        $this->supplierB = Supplier::factory()->create([
            'name' => 'Supplier B',
            'enabled' => true,
            'capabilities' => [
                'cancellation_window_hours' => 48,
            ],
        ]);
    }

    /** @test */
    public function it_creates_order_lines_with_supplier_selection()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 10,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        // Mock the supplier selector
        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000, // €50.00
                    'sell_price' => 8000, // €80.00
                    'lead_time' => 48, // 48 hours
                    'shipping_options' => [],
                ],
                'score' => 85.5,
            ],
            fallbacks: collect([
                [
                    'supplier' => $this->supplierB,
                    'supplier_product' => $supplierProduct,
                    'quote' => [
                        'cost_price' => 5500,
                        'sell_price' => 8500,
                        'lead_time' => 72,
                    ],
                    'score' => 72.3,
                ],
            ]),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals(1, $order->lines()->count());

        $orderLine = $order->lines->first();
        $this->assertNotNull($orderLine);
        $this->assertEquals($variant->id, $orderLine->purchasable_id);
        $this->assertEquals(10, $orderLine->quantity);

        // Assert supplier order was created
        $supplierOrder = SupplierOrder::query()
            ->where('order_line_id', $orderLine->id)
            ->first();

        $this->assertNotNull($supplierOrder);
        $this->assertEquals($this->supplierA->id, $supplierOrder->supplier_id);
        $this->assertEquals(5000, $supplierOrder->estimated_cost_price);
        $this->assertEquals(SupplierOrder::STATUS_PENDING, $supplierOrder->status);
        $this->assertNotNull($supplierOrder->cancellation_deadline);
        $this->assertNotNull($supplierOrder->estimated_delivery_date);
        $this->assertEquals('not_required', $supplierOrder->artwork_status);

        // Assert external data
        $this->assertIsArray($supplierOrder->external_data);
        $this->assertEquals(85.5, $supplierOrder->external_data['selection_score']);
        $this->assertArrayHasKey('selection_reason', $supplierOrder->external_data);
        $this->assertArrayHasKey('alternatives', $supplierOrder->external_data);
        $this->assertArrayHasKey('quote', $supplierOrder->external_data);
        $this->assertArrayHasKey('selected_at', $supplierOrder->external_data);
    }

    /** @test */
    public function it_creates_multiple_order_lines_with_different_suppliers()
    {
        // Arrange
        $product1 = Product::factory()->create();
        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product1->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant1->getMorphClass(),
            'priceable_id' => $variant1->id,
        ]);

        $product2 = Product::factory()->create();
        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product2->id,
        ]);

        Price::factory()->create([
            'price' => 10000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant2->getMorphClass(),
            'priceable_id' => $variant2->id,
        ]);

        $supplierProduct1 = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant1->id,
            'active' => true,
        ]);

        $supplierProduct2 = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierB->id,
            'product_variant_id' => $variant2->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant1->id,
            'quantity' => 5,
        ]);

        CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant2->id,
            'quantity' => 3,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        // Mock responses for both selections
        $mockSelection1 = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct1,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelection2 = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierB,
                'supplier_product' => $supplierProduct2,
                'quote' => [
                    'cost_price' => 7000,
                    'sell_price' => 10000,
                    'lead_time' => 96,
                ],
                'score' => 78.2,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->twice()
            ->andReturn($mockSelection1, $mockSelection2);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $this->assertEquals(2, $order->lines()->count());

        $supplierOrders = SupplierOrder::query()
            ->whereIn('order_line_id', $order->lines->pluck('id'))
            ->get();

        $this->assertEquals(2, $supplierOrders->count());

        // Assert different suppliers were used
        $this->assertTrue(
            $supplierOrders->pluck('supplier_id')->contains($this->supplierA->id)
        );
        $this->assertTrue(
            $supplierOrders->pluck('supplier_id')->contains($this->supplierB->id)
        );
    }

    /** @test */
    public function it_detects_artwork_requirement_from_meta()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
            'meta' => [
                'has_artwork' => true,
            ],
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $this->assertEquals('pending', $supplierOrder->artwork_status);
        // Artwork requires approval
        $this->assertTrue((bool) $supplierOrder->requires_approval);
    }

    /** @test */
    public function it_does_not_require_approval_when_no_artwork()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
            'meta' => [],
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $this->assertFalse((bool) $supplierOrder->requires_approval);
    }

    /** @test */
    public function it_calculates_delivery_date_from_lead_time()
    {
        // Arrange
        Carbon::setTestNow('2024-01-15 10:00:00');

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        // 48 hours = 6 business days (48/8 = 6)
        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $this->assertNotNull($supplierOrder->estimated_delivery_date);

        // Should be 6 weekdays later
        $expectedDate = Carbon::parse('2024-01-15')->addWeekdays(6);
        $this->assertEquals(
            $expectedDate->format('Y-m-d'),
            $supplierOrder->estimated_delivery_date->format('Y-m-d')
        );

        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function it_calculates_cancellation_deadline()
    {
        // Arrange
        Carbon::setTestNow('2024-01-15 10:00:00');

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA, // Has 24h cancellation window
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $this->assertNotNull($supplierOrder->cancellation_deadline);

        // Should be 24 hours later
        $expectedDeadline = Carbon::parse('2024-01-15 10:00:00')->addHours(24);
        $this->assertEquals(
            $expectedDeadline->format('Y-m-d H:i'),
            $supplierOrder->cancellation_deadline->format('Y-m-d H:i')
        );

        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function it_includes_alternatives_in_external_data()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct1 = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $supplierProduct2 = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierB->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct1,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect([
                [
                    'supplier' => $this->supplierB,
                    'supplier_product' => $supplierProduct2,
                    'quote' => [
                        'cost_price' => 5500,
                        'sell_price' => 8500,
                        'lead_time' => 72,
                    ],
                    'score' => 72.3,
                ],
            ]),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $externalData = $supplierOrder->external_data;

        $this->assertArrayHasKey('alternatives', $externalData);
        $this->assertCount(1, $externalData['alternatives']);

        $alternative = $externalData['alternatives'][0];
        $this->assertEquals($this->supplierB->id, $alternative['supplier_id']);
        $this->assertEquals($this->supplierB->name, $alternative['supplier_name']);
        $this->assertEquals(72.3, $alternative['score']);
        $this->assertEquals(5500, $alternative['cost_price']);
    }

    /** @test */
    public function it_handles_gracefully_when_no_suppliers_available()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andThrow(new NoSuppliersAvailableException());

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert - order should still be created without supplier order
        $this->assertNotNull($result);
        $this->assertEquals(1, $order->lines()->count());

        // No supplier order should be created
        $supplierOrder = SupplierOrder::query()
            ->where('order_line_id', $order->lines->first()->id)
            ->first();

        $this->assertNull($supplierOrder);
    }

    /** @test */
    public function it_handles_null_delivery_date_when_no_lead_time()
    {
        // Arrange
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $this->supplierA->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        // No lead_time in quote
        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $this->supplierA,
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $this->assertNull($supplierOrder->estimated_delivery_date);
    }

    /** @test */
    public function it_handles_null_cancellation_deadline_when_no_window()
    {
        // Arrange
        $supplier = Supplier::factory()->create([
            'name' => 'Supplier No Window',
            'enabled' => true,
            'capabilities' => [], // No cancellation window
        ]);

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        Price::factory()->create([
            'price' => 8000,
            'min_quantity' => 1,
            'currency_id' => $this->currency->id,
            'priceable_type' => $variant->getMorphClass(),
            'priceable_id' => $variant->id,
        ]);

        $supplierProduct = SupplierProduct::factory()->create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'active' => true,
        ]);

        $cart = Cart::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $cartLine = CartLine::factory()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => ProductVariant::modelClass(),
            'purchasable_id' => $variant->id,
            'quantity' => 1,
        ]);

        $cart->recalculate();

        $order = Order::factory()->create([
            'cart_id' => $cart->id,
            'currency_code' => 'EUR',
        ]);

        $mockSelection = new SupplierSelectionResult(
            primary: [
                'supplier' => $supplier,
                'supplier_product' => $supplierProduct,
                'quote' => [
                    'cost_price' => 5000,
                    'sell_price' => 8000,
                    'lead_time' => 48,
                ],
                'score' => 85.5,
            ],
            fallbacks: collect(),
            allQuotes: collect()
        );

        $mockSelector = $this->mock(RuntimeSupplierSelector::class);
        $mockSelector->shouldReceive('selectBestSupplier')
            ->once()
            ->andReturn($mockSelection);

        $pipeline = new CreateOrderLines($mockSelector);

        // Act
        $result = $pipeline->handle($order, fn ($order) => $order);

        // Assert
        $supplierOrder = SupplierOrder::first();
        $this->assertNull($supplierOrder->cancellation_deadline);
    }
}
