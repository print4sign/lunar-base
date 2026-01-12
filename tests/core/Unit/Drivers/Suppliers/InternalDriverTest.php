<?php

use Illuminate\Support\Facades\DB;
use Lunar\Drivers\Suppliers\InternalDriver;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;
use Lunar\Models\OrderLine;
use Lunar\Models\Order;

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->supplier = Supplier::factory()->create([
        'handle' => 'internal',
        'name' => 'Internal Fulfillment',
        'driver' => 'internal',
    ]);

    $this->driver = new InternalDriver();
    $this->driver->setSupplier($this->supplier);
});

test('capabilities returns correct array', function () {
    $capabilities = $this->driver->capabilities();

    expect($capabilities)->toBeArray()
        ->toHaveCount(2)
        ->toContain('ordering')
        ->toContain('tracking');
});

test('supports ordering capability', function () {
    expect($this->driver->supports('ordering'))->toBeTrue();
});

test('supports tracking capability', function () {
    expect($this->driver->supports('tracking'))->toBeTrue();
});

test('does not support catalog_sync capability', function () {
    expect($this->driver->supports('catalog_sync'))->toBeFalse();
});

test('does not support pricing capability', function () {
    expect($this->driver->supports('pricing'))->toBeFalse();
});

test('syncCatalog returns empty collection', function () {
    $result = $this->driver->syncCatalog();

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toBeEmpty();
});

test('getProduct returns null', function () {
    $result = $this->driver->getProduct('test-product');

    expect($result)->toBeNull();
});

test('getConfiguratorSchema returns null', function () {
    $result = $this->driver->getConfiguratorSchema('test-product');

    expect($result)->toBeNull();
});

test('configure returns basic ConfiguratorResponse with can_order true', function () {
    $result = $this->driver->configure('test-product', []);

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse::class)
        ->and($result->canOrder)->toBeTrue()
        ->and($result->productCode)->toBe('test-product')
        ->and($result->availableOptions)->toBeEmpty()
        ->and($result->selectedOptions)->toBeEmpty();
});

test('getPrice returns PriceResponse with zero cost and sell price', function () {
    $result = $this->driver->getPrice('test-product', ['quantity' => 1]);

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\Supplier\PriceResponse::class)
        ->and($result->costPrice)->toBe(0)
        ->and($result->sellPrice)->toBe(0)
        ->and($result->currency)->toBe('EUR')
        ->and($result->meta)->toHaveKey('internal', true);
});

test('getBulkPrices returns empty collection', function () {
    $items = [
        ['external_id' => 'product-1', 'quantity' => 1],
        ['external_id' => 'product-2', 'quantity' => 2],
    ];

    $result = $this->driver->getBulkPrices($items);

    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->toBeEmpty();
});

test('getPriceWithShipping delegates to getPrice', function () {
    $result = $this->driver->getPriceWithShipping(
        'test-product',
        ['quantity' => 1],
        ['country' => 'NL']
    );

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\Supplier\PriceResponse::class)
        ->and($result->costPrice)->toBe(0)
        ->and($result->sellPrice)->toBe(0);
});

test('submitOrder generates valid internal external ID', function () {
    $order = Order::factory()->create(['reference' => 'ORD-12345']);
    $orderLine = OrderLine::factory()->create([
        'order_id' => $order->id,
        'id' => 999,
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'order_id' => $order->id,
        'order_line_id' => $orderLine->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $result = $this->driver->submitOrder($supplierOrder);

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\Supplier\OrderResponse::class)
        ->and($result->success)->toBeTrue()
        ->and($result->externalId)->toStartWith('INTERNAL-ORD-12345-999')
        ->and($result->status)->toBe('submitted');
});

test('submitOrder external ID format includes reference and line ID', function () {
    $order = Order::factory()->create(['reference' => 'TEST-001']);
    $orderLine = OrderLine::factory()->create([
        'order_id' => $order->id,
        'id' => 42,
    ]);
    $supplierOrder = SupplierOrder::factory()->create([
        'order_id' => $order->id,
        'order_line_id' => $orderLine->id,
        'supplier_id' => $this->supplier->id,
    ]);

    $result = $this->driver->submitOrder($supplierOrder);

    expect($result->externalId)->toStartWith('INTERNAL-TEST-001-42');
});

test('getOrderStatus returns processing status', function () {
    $result = $this->driver->getOrderStatus('INTERNAL-TEST-001-42');

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\Supplier\StatusResponse::class)
        ->and($result->status)->toBe('processing')
        ->and($result->externalId)->toBe('INTERNAL-TEST-001-42');
});

test('cancelOrder always returns true', function () {
    $result = $this->driver->cancelOrder('INTERNAL-TEST-001-42');

    expect($result)->toBeTrue();
});

test('getHiddenSectionsForDynamic returns empty array', function () {
    $result = $this->driver->getHiddenSectionsForDynamic();

    expect($result)->toBeArray()
        ->toBeEmpty();
});

test('getConfiguratorComponent returns null', function () {
    $result = $this->driver->getConfiguratorComponent();

    expect($result)->toBeNull();
});

test('getDynamicPrice delegates to getPrice', function () {
    $result = $this->driver->getDynamicPrice('test-product', ['quantity' => 1]);

    expect($result)->toBeInstanceOf(\Lunar\Base\DataTransferObjects\Supplier\PriceResponse::class)
        ->and($result->costPrice)->toBe(0)
        ->and($result->sellPrice)->toBe(0);
});

test('getCartLinePipelines returns empty array', function () {
    $result = $this->driver->getCartLinePipelines();

    expect($result)->toBeArray()
        ->toBeEmpty();
});
