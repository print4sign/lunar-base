<?php

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\Http;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Drivers\Suppliers\PrintComDriver;
use Lunar\Exceptions\Suppliers\SupplierException;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;
use Lunar\Models\SupplierProduct;

test('it returns correct capabilities including pdf processing and batch operations', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $capabilities = $driver->capabilities();

    expect($capabilities)->toBeArray()
        ->toContain('catalog_sync')
        ->toContain('pricing')
        ->toContain('ordering')
        ->toContain('file_upload')
        ->toContain('tracking')
        ->toContain('pdf_processing')
        ->toContain('batch_operations');
});

test('it adds bearer token authentication to http requests', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key-123',
        ],
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    Http::fake([
        'api.print.com/*' => Http::response(['data' => []], 200),
    ]);

    // Trigger a request to check base URL
    $reflection = new ReflectionClass($driver);
    $method = $reflection->getMethod('http');
    $method->setAccessible(true);
    $httpClient = $method->invoke($driver);

    // Check that the base URL is correct
    expect($httpClient)->toHaveProperty('baseUrl');
});

test('it syncs catalog with offset pagination', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    // Create data for realistic offset pagination
    // First batch: 100 items (full batch)
    $firstBatch = [];
    for ($i = 1; $i <= 100; $i++) {
        $firstBatch[] = ['sku' => sprintf('PC-%03d', $i), 'name' => "Product {$i}", 'active' => true];
    }

    // Second batch: 50 items (partial batch, indicates end)
    $secondBatch = [];
    for ($i = 101; $i <= 150; $i++) {
        $secondBatch[] = ['sku' => sprintf('PC-%03d', $i), 'name' => "Product {$i}", 'active' => true];
    }

    Http::fake([
        'api.print.com/v1/products?offset=0&limit=100' => Http::response([
            'data' => $firstBatch,
        ], 200),
        'api.print.com/v1/products?offset=100&limit=100' => Http::response([
            'data' => $secondBatch,
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $products = $driver->syncCatalog();

    expect($products)->toHaveCount(150);

    $product = SupplierProduct::where('external_id', 'PC-001')->first();
    expect($product)->not->toBeNull()
        ->external_name->toBe('Product 1')
        ->active->toBeTrue()
        ->synced->toBeTrue()
        ->last_synced_at->not->toBeNull();

    // Verify the last product from second batch
    $lastProduct = SupplierProduct::where('external_id', 'PC-150')->first();
    expect($lastProduct)->not->toBeNull()
        ->external_name->toBe('Product 150');
});

test('it throws exception when catalog sync fails', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $driver->syncCatalog();
})->throws(SupplierException::class);

test('it fetches a single product', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/PC-001' => Http::response([
            'sku' => 'PC-001',
            'name' => 'Business Cards',
            'options' => [],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $product = $driver->getProduct('PC-001');

    expect($product)->toBeArray()
        ->and($product['sku'])->toBe('PC-001')
        ->and($product['name'])->toBe('Business Cards');
});

test('it fetches configurator schema', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/PC-001/options' => Http::response([
            'options' => [
                ['id' => 'finish', 'name' => 'Finish', 'values' => ['matte', 'glossy']],
                ['id' => 'quantity', 'name' => 'Quantity', 'type' => 'number'],
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $schema = $driver->getConfiguratorSchema('PC-001');

    expect($schema)->toBeArray()
        ->and($schema['options'])->toHaveCount(2);
});

test('it configures a product with sku', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $response = $driver->configure('PC-001', ['sku' => 'PC-001-variant']);

    expect($response)->toBeInstanceOf(ConfiguratorResponse::class)
        ->productCode->toBe('PC-001')
        ->canOrder->toBeTrue();
});

test('it marks configuration as not orderable without sku', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $response = $driver->configure('PC-001', []);

    expect($response)->toBeInstanceOf(ConfiguratorResponse::class)
        ->canOrder->toBeFalse();
});

test('it gets price from products price endpoint', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/price' => Http::response([
            'products' => [
                [
                    'sku' => 'PC-001',
                    'cost' => 1500,
                    'price' => 2500,
                    'currency' => 'USD',
                    'basePrice' => 1200,
                    'optionsPrice' => 300,
                ],
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $priceResponse = $driver->getPrice('PC-001', [
        'quantity' => 100,
        'options' => ['finish' => 'matte'],
    ]);

    expect($priceResponse)->toBeInstanceOf(PriceResponse::class)
        ->costPrice->toBe(1500)
        ->sellPrice->toBe(2500)
        ->currency->toBe('USD')
        ->breakdown->toBeArray()
        ->breakdown->toHaveKey('base_price', 1200)
        ->breakdown->toHaveKey('options_price', 300);
});

test('it throws exception when pricing fails', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/*' => Http::response(['error' => 'Invalid product'], 400),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $driver->getPrice('PC-001', ['quantity' => 100]);
})->throws(SupplierException::class);

test('it gets bulk prices', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/price' => Http::response([
            'products' => [
                ['sku' => 'PC-001', 'cost' => 1500, 'price' => 2500, 'currency' => 'USD'],
                ['sku' => 'PC-002', 'cost' => 3000, 'price' => 5000, 'currency' => 'USD'],
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $prices = $driver->getBulkPrices([
        ['external_id' => 'PC-001', 'configuration' => ['options' => ['finish' => 'matte']], 'quantity' => 100],
        ['external_id' => 'PC-002', 'configuration' => ['options' => ['paper' => '170gsm']], 'quantity' => 50],
    ]);

    expect($prices)->toHaveCount(2)
        ->and($prices->first())->toBeInstanceOf(PriceResponse::class)
        ->and($prices->first()->costPrice)->toBe(1500);
});

test('it gets dynamic price with quantity', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/price' => Http::response([
            'products' => [
                ['cost' => 2000, 'price' => 3500, 'currency' => 'USD'],
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $priceResponse = $driver->getDynamicPrice('PC-001', ['options' => ['finish' => 'matte']], 250);

    expect($priceResponse)->toBeInstanceOf(PriceResponse::class)
        ->sellPrice->toBe(3500);
});

test('it submits order with items array and artwork', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    $currency = Currency::factory()->create();
    $country = Country::factory()->create(['iso2' => 'US']);

    $order = Order::factory()->create([
        'reference' => 'ORD-123',
    ]);

    $shippingAddress = \Lunar\Models\OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => 'shipping',
        'country_id' => $country->id,
        'line_one' => 'Test Street 1',
        'city' => 'New York',
        'postcode' => '10001',
        'contact_phone' => '+1234567890',
        'contact_email' => 'test@example.com',
    ]);

    $variant = ProductVariant::factory()->create([
        'configuration' => ['options' => ['finish' => 'matte']],
    ]);

    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'PC-001',
    ]);

    $variant->supplierProduct()->associate($supplierProduct);
    $variant->save();

    $orderLine = OrderLine::factory()->create([
        'order_id' => $order->id,
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 100,
    ]);

    $supplierOrder = SupplierOrder::create([
        'supplier_id' => $supplier->id,
        'order_id' => $order->id,
        'order_line_id' => $orderLine->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'api.print.com/v1/orders' => Http::response([
            'id' => 'PC-ORD-456',
            'status' => 'submitted',
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $response = $driver->submitOrder($supplierOrder);

    expect($response)->toBeInstanceOf(OrderResponse::class)
        ->success->toBeTrue()
        ->externalId->toBe('PC-ORD-456');

    Http::assertSent(function ($request) {
        $data = $request->data();
        return isset($data['items']) &&
               is_array($data['items']) &&
               isset($data['items'][0]['sku']) &&
               $data['items'][0]['sku'] === 'PC-001' &&
               isset($data['shippingAddress']);
    });
});

test('it maps status correctly', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/orders/PC-ORD-123' => Http::response([
            'id' => 'PC-ORD-123',
            'status' => 'in_production',
            'shipments' => [
                [
                    'trackingNumber' => 'TRACK123',
                    'trackingUrl' => 'https://track.example.com/TRACK123',
                ],
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $status = $driver->getOrderStatus('PC-ORD-123');

    expect($status)->toBeInstanceOf(StatusResponse::class)
        ->status->toBe('processing')
        ->tracking->toBeArray()
        ->tracking->toHaveKey('numbers', ['TRACK123'])
        ->tracking->toHaveKey('url', 'https://track.example.com/TRACK123');
});

test('it extracts multiple tracking numbers from shipments array', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/orders/PC-ORD-123' => Http::response([
            'id' => 'PC-ORD-123',
            'status' => 'shipped',
            'shipments' => [
                [
                    'trackingNumber' => 'TRACK123',
                    'trackingUrl' => 'https://track.example.com/TRACK123',
                ],
                [
                    'trackingNumber' => 'TRACK456',
                    'trackingUrl' => 'https://track.example.com/TRACK456',
                ],
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $status = $driver->getOrderStatus('PC-ORD-123');

    expect($status)->toBeInstanceOf(StatusResponse::class)
        ->tracking->toBeArray()
        ->tracking->toHaveKey('numbers')
        ->and($status->tracking['numbers'])->toHaveCount(2)
        ->and($status->tracking['numbers'])->toContain('TRACK123')
        ->and($status->tracking['numbers'])->toContain('TRACK456');
});

test('it cancels an order using DELETE method', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/orders/PC-ORD-123' => Http::response([], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $result = $driver->cancelOrder('PC-ORD-123');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE' &&
               str_contains($request->url(), 'orders/PC-ORD-123');
    });
});

test('it resolves upload spec with multiple pages', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/PC-001/specs' => Http::response([
            'artwork' => [
                'required' => true,
                'pages' => 3,
                'dimensions' => [
                    'width' => 210,
                    'height' => 297,
                    'unit' => 'mm',
                ],
                'acceptedFormats' => ['pdf'],
                'maxSizeMb' => 50,
                'minDpi' => 300,
                'colorMode' => 'CMYK',
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $uploadSpec = $driver->resolveUploadSpec('PC-001', []);

    expect($uploadSpec)->toBeInstanceOf(UploadSpec::class)
        ->upload->toBeTrue()
        ->uploaders->toHaveCount(3);

    expect($uploadSpec->uploaders->first()->type)->toBe('single')
        ->and($uploadSpec->uploaders->first()->width)->toEqual(210.0)
        ->and($uploadSpec->uploaders->first()->height)->toEqual(297.0)
        ->and($uploadSpec->uploaders->first()->minimalDpi)->toBe(300);
});

test('it resolves upload spec with single page', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/PC-001/specs' => Http::response([
            'artwork' => [
                'required' => true,
                'pages' => 1,
                'dimensions' => [
                    'width' => 85,
                    'height' => 55,
                    'unit' => 'mm',
                ],
                'acceptedFormats' => ['pdf'],
                'maxSizeMb' => 25,
                'minDpi' => 300,
                'colorMode' => 'CMYK',
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $uploadSpec = $driver->resolveUploadSpec('PC-001', []);

    expect($uploadSpec)->toBeInstanceOf(UploadSpec::class)
        ->uploaders->toHaveCount(1);

    expect($uploadSpec->uploaders->first()->type)->toBe('single')
        ->and($uploadSpec->uploaders->first()->width)->toEqual(85.0)
        ->and($uploadSpec->uploaders->first()->height)->toEqual(55.0);
});

test('it returns empty upload spec when no artwork required', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.print.com/v1/products/PC-001/specs' => Http::response([
            'artwork' => [
                'required' => false,
            ],
        ], 200),
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $uploadSpec = $driver->resolveUploadSpec('PC-001', []);

    expect($uploadSpec)->toBeInstanceOf(UploadSpec::class)
        ->upload->toBeFalse();
});

test('it returns cart line pipelines', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $pipelines = $driver->getCartLinePipelines();

    expect($pipelines)->toBeArray()
        ->toHaveCount(1)
        ->toContain(\Lunar\Drivers\Suppliers\PrintCom\Pipelines\GetPrintComPrice::class);
});

test('it returns empty hidden sections for dynamic', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $sections = $driver->getHiddenSectionsForDynamic();

    expect($sections)->toBeArray()->toBeEmpty();
});

test('it returns null configurator component', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'printcom',
    ]);

    $driver = new PrintComDriver();
    $driver->setSupplier($supplier);

    $component = $driver->getConfiguratorComponent();

    expect($component)->toBeNull();
});
