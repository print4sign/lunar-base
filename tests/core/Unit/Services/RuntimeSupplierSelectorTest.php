<?php

uses(\Lunar\Tests\Core\TestCase::class);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Lunar\DataTransferObjects\SupplierSelectionResult;
use Lunar\Exceptions\Suppliers\NoSuppliersAvailableException;
use Lunar\Managers\SupplierManager;
use Lunar\Models\Address;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Services\RuntimeSupplierSelector;

uses(RefreshDatabase::class)->group('supplier-selection');

beforeEach(function () {
    // Create base test data
    $this->supplier1 = Supplier::factory()->create([
        'name' => 'Probo',
        'driver' => 'probo',
        'enabled' => true,
    ]);

    $this->supplier2 = Supplier::factory()->create([
        'name' => 'HelloPrint',
        'driver' => 'helloprint',
        'enabled' => true,
    ]);

    $this->supplier3 = Supplier::factory()->create([
        'name' => 'Internal',
        'driver' => 'internal',
        'enabled' => true,
    ]);

    $this->variant = ProductVariant::factory()->create([
        'sku' => 'TEST-001',
    ]);

    $this->order = Order::factory()->create([
        'status' => 'awaiting-payment',
    ]);

    $this->orderLine = OrderLine::factory()->create([
        'order_id' => $this->order->id,
        'purchasable_id' => $this->variant->id,
        'purchasable_type' => ProductVariant::class,
        'quantity' => 100,
    ]);
});

test('selectBestSupplier returns supplier with highest score', function () {
    // Create supplier products
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => true,
    ]);

    $sp2 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier2->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'hello-456',
        'active' => true,
    ]);

    // Mock the SupplierManager
    $supplierManager = Mockery::mock(SupplierManager::class);

    // Mock driver responses
    $driver1 = Mockery::mock(\Lunar\Base\SupplierDriverInterface::class);
    $driver1->shouldReceive('getPriceWithShipping')
        ->andReturn(new \Lunar\Base\DataTransferObjects\Supplier\PriceResponse(
            costPrice: 5000, // €50
            sellPrice: 7500, // €75
            currency: 'EUR',
            breakdown: ['production_hours' => 48],
            meta: ['shipping_options' => [['priceInCents' => 500]]]
        ));

    $driver2 = Mockery::mock(\Lunar\Base\SupplierDriverInterface::class);
    $driver2->shouldReceive('getPriceWithShipping')
        ->andReturn(new \Lunar\Base\DataTransferObjects\Supplier\PriceResponse(
            costPrice: 6000, // €60 (more expensive)
            sellPrice: 8500,
            currency: 'EUR',
            breakdown: ['production_hours' => 72], // Slower
            meta: ['shipping_options' => [['priceInCents' => 800]]]
        ));

    $supplierManager->shouldReceive('supplier')
        ->with(Mockery::on(fn($s) => $s->id === $this->supplier1->id))
        ->andReturn($driver1);

    $supplierManager->shouldReceive('supplier')
        ->with(Mockery::on(fn($s) => $s->id === $this->supplier2->id))
        ->andReturn($driver2);

    $selector = new RuntimeSupplierSelector($supplierManager);

    $result = $selector->selectBestSupplier($this->orderLine, $this->order);

    expect($result)->toBeInstanceOf(SupplierSelectionResult::class);
    expect($result->primary['supplier']->id)->toBe($this->supplier1->id);
    expect($result->primary['score'])->toBeGreaterThan(0);
    expect($result->fallbacks)->toHaveCount(1);
    expect($result->fallbacks->first()['supplier']->id)->toBe($this->supplier2->id);
});

test('throws exception when no suppliers available', function () {
    // Variant with no supplier products
    $selector = new RuntimeSupplierSelector(app(SupplierManager::class));

    $selector->selectBestSupplier($this->orderLine, $this->order);
})->throws(NoSuppliersAvailableException::class);

test('throws exception when all suppliers fail to provide quotes', function () {
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => true,
    ]);

    // Mock supplier manager that throws exceptions
    $supplierManager = Mockery::mock(SupplierManager::class);
    $driver = Mockery::mock(\Lunar\Base\SupplierDriverInterface::class);
    $driver->shouldReceive('getPriceWithShipping')
        ->andThrow(new \Exception('API Error'));

    $supplierManager->shouldReceive('supplier')
        ->andReturn($driver);

    Log::shouldReceive('warning')->once();

    $selector = new RuntimeSupplierSelector($supplierManager);

    $selector->selectBestSupplier($this->orderLine, $this->order);
})->throws(NoSuppliersAvailableException::class);

test('calculatePriceScore returns higher score for lower prices', function () {
    $supplierManager = app(SupplierManager::class);
    $selector = new RuntimeSupplierSelector($supplierManager);

    $reflection = new \ReflectionClass($selector);
    $method = $reflection->getMethod('calculatePriceScore');
    $method->setAccessible(true);

    $score1 = $method->invoke($selector, 5000, ['min' => 5000, 'max' => 10000]);
    $score2 = $method->invoke($selector, 10000, ['min' => 5000, 'max' => 10000]);

    expect($score1)->toBe(100.0);
    expect($score2)->toBe(0.0);
});

test('calculateLeadTimeScore returns higher score for faster delivery', function () {
    $supplierManager = app(SupplierManager::class);
    $selector = new RuntimeSupplierSelector($supplierManager);

    $reflection = new \ReflectionClass($selector);
    $method = $reflection->getMethod('calculateLeadTimeScore');
    $method->setAccessible(true);

    $score24h = $method->invoke($selector, 24); // 24 hours
    $score1week = $method->invoke($selector, 168); // 1 week

    expect($score24h)->toBe(100.0);
    expect($score1week)->toBeGreaterThan(0);
    expect($score1week)->toBeLessThan($score24h);
});

test('calculateShippingScore returns higher score for cheaper shipping', function () {
    $supplierManager = app(SupplierManager::class);
    $selector = new RuntimeSupplierSelector($supplierManager);

    $reflection = new \ReflectionClass($selector);
    $method = $reflection->getMethod('calculateShippingScore');
    $method->setAccessible(true);

    $scoreFree = $method->invoke($selector, [['priceInCents' => 0]]);
    $scoreExpensive = $method->invoke($selector, [['priceInCents' => 2000]]);

    expect($scoreFree)->toBe(100.0);
    expect($scoreExpensive)->toBe(0.0);
});

test('uses variant specific supplier product when configured', function () {
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => true,
    ]);

    // Set specific supplier product on variant
    $this->variant->update(['supplier_product_id' => $sp1->id]);

    $supplierManager = Mockery::mock(SupplierManager::class);
    $driver = Mockery::mock(\Lunar\Base\SupplierDriverInterface::class);
    $driver->shouldReceive('getPriceWithShipping')
        ->andReturn(new \Lunar\Base\DataTransferObjects\Supplier\PriceResponse(
            costPrice: 5000,
            sellPrice: 7500,
            currency: 'EUR',
            breakdown: ['production_hours' => 48],
            meta: ['shipping_options' => [['priceInCents' => 500]]]
        ));

    $supplierManager->shouldReceive('supplier')
        ->with(Mockery::on(fn($s) => $s->id === $this->supplier1->id))
        ->andReturn($driver);

    $selector = new RuntimeSupplierSelector($supplierManager);

    $result = $selector->selectBestSupplier($this->orderLine, $this->order);

    expect($result->primary['supplier']->id)->toBe($this->supplier1->id);
});

test('skips disabled suppliers', function () {
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => true,
    ]);

    // Disable supplier
    $this->supplier1->update(['enabled' => false]);

    $selector = new RuntimeSupplierSelector(app(SupplierManager::class));

    $selector->selectBestSupplier($this->orderLine, $this->order);
})->throws(NoSuppliersAvailableException::class);

test('skips inactive supplier products', function () {
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => false, // Inactive
    ]);

    $selector = new RuntimeSupplierSelector(app(SupplierManager::class));

    $selector->selectBestSupplier($this->orderLine, $this->order);
})->throws(NoSuppliersAvailableException::class);

test('getReason returns human readable explanation', function () {
    $result = new SupplierSelectionResult(
        primary: [
            'supplier' => $this->supplier1,
            'score' => 85.5,
            'quote' => [
                'cost_price' => 5000,
            ],
        ],
        fallbacks: collect(),
        allQuotes: collect()
    );

    $reason = $result->getReason();

    expect($reason)->toContain('85.5');
    expect($reason)->toContain('€50');
});

test('handles missing priority field gracefully', function () {
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => true,
        // No priority set
    ]);

    $supplierManager = Mockery::mock(SupplierManager::class);
    $driver = Mockery::mock(\Lunar\Base\SupplierDriverInterface::class);
    $driver->shouldReceive('getPriceWithShipping')
        ->andReturn(new \Lunar\Base\DataTransferObjects\Supplier\PriceResponse(
            costPrice: 5000,
            sellPrice: 7500,
            currency: 'EUR',
            breakdown: ['production_hours' => 48],
            meta: ['shipping_options' => [['priceInCents' => 500]]]
        ));

    $supplierManager->shouldReceive('supplier')
        ->with(Mockery::on(fn($s) => $s->id === $this->supplier1->id))
        ->andReturn($driver);

    $selector = new RuntimeSupplierSelector($supplierManager);

    $result = $selector->selectBestSupplier($this->orderLine, $this->order);

    // Should not throw error, should use default priority of 50
    expect($result->primary['score'])->toBeGreaterThan(0);
});

test('returns up to 2 fallback suppliers', function () {
    $sp1 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier1->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'probo-123',
        'active' => true,
    ]);

    $sp2 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier2->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'hello-456',
        'active' => true,
    ]);

    $sp3 = SupplierProduct::factory()->create([
        'supplier_id' => $this->supplier3->id,
        'product_variant_id' => $this->variant->id,
        'external_id' => 'internal-789',
        'active' => true,
    ]);

    $supplierManager = Mockery::mock(SupplierManager::class);

    foreach ([$this->supplier1, $this->supplier2, $this->supplier3] as $supplier) {
        $driver = Mockery::mock(\Lunar\Base\SupplierDriverInterface::class);
        $driver->shouldReceive('getPriceWithShipping')
            ->andReturn(new \Lunar\Base\DataTransferObjects\Supplier\PriceResponse(
                costPrice: 5000,
                sellPrice: 7500,
                currency: 'EUR',
                breakdown: ['production_hours' => 48],
                meta: ['shipping_options' => [['priceInCents' => 500]]]
            ));

        $supplierManager->shouldReceive('supplier')
            ->with(Mockery::on(fn($s) => $s->id === $supplier->id))
            ->andReturn($driver);
    }

    $selector = new RuntimeSupplierSelector($supplierManager);

    $result = $selector->selectBestSupplier($this->orderLine, $this->order);

    expect($result->fallbacks)->toHaveCount(2);
    expect($result->allQuotes)->toHaveCount(3);
});
