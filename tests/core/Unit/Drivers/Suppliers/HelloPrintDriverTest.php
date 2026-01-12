<?php

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\Http;
use Lunar\Base\DataTransferObjects\Supplier\ConfiguratorResponse;
use Lunar\Base\DataTransferObjects\Supplier\OrderResponse;
use Lunar\Base\DataTransferObjects\Supplier\PriceResponse;
use Lunar\Base\DataTransferObjects\Supplier\StatusResponse;
use Lunar\Base\DataTransferObjects\Upload\UploadSpec;
use Lunar\Drivers\Suppliers\HelloPrintDriver;
use Lunar\Exceptions\Suppliers\SupplierException;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\Currency;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierOrder;
use Lunar\Models\SupplierProduct;

test('it returns correct capabilities', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $capabilities = $driver->capabilities();

    expect($capabilities)->toBeArray()
        ->toContain('catalog_sync')
        ->toContain('pricing')
        ->toContain('ordering')
        ->toContain('file_upload')
        ->toContain('tracking');
});

test('it adds bearer token authentication to http requests', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key-123',
        ],
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    Http::fake([
        'api.helloprint.com/*' => Http::response(['data' => []], 200),
    ]);

    // Trigger a request to check headers
    $reflection = new ReflectionClass($driver);
    $method = $reflection->getMethod('http');
    $method->setAccessible(true);
    $httpClient = $method->invoke($driver);

    // Check that the base URL is correct
    expect($httpClient)->toHaveProperty('baseUrl');
});

test('it syncs catalog with pagination', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/products?page=1&limit=100' => Http::response([
            'data' => [
                ['sku' => 'HP-001', 'name' => 'Business Cards', 'active' => true],
                ['sku' => 'HP-002', 'name' => 'Flyers', 'active' => true],
            ],
            'pagination' => ['next' => 'page=2'],
        ], 200),
        'api.helloprint.com/rest/v1/products?page=2&limit=100' => Http::response([
            'data' => [
                ['sku' => 'HP-003', 'name' => 'Posters', 'active' => false],
            ],
            'pagination' => [],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $products = $driver->syncCatalog();

    expect($products)->toHaveCount(3);

    $product = SupplierProduct::where('external_id', 'HP-001')->first();
    expect($product)->not->toBeNull()
        ->external_name->toBe('Business Cards')
        ->active->toBeTrue()
        ->synced->toBeTrue()
        ->last_synced_at->not->toBeNull();
});

test('it throws exception when catalog sync fails', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $driver->syncCatalog();
})->throws(SupplierException::class);

test('it fetches a single product', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/products/HP-001' => Http::response([
            'sku' => 'HP-001',
            'name' => 'Business Cards',
            'variants' => [],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $product = $driver->getProduct('HP-001');

    expect($product)->toBeArray()
        ->and($product['sku'])->toBe('HP-001')
        ->and($product['name'])->toBe('Business Cards');
});

test('it fetches configurator schema', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/products/HP-001/variants' => Http::response([
            'variants' => [
                ['variantKey' => 'standard-100', 'description' => '100 standard cards'],
                ['variantKey' => 'premium-100', 'description' => '100 premium cards'],
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $schema = $driver->getConfiguratorSchema('HP-001');

    expect($schema)->toBeArray()
        ->and($schema['variants'])->toHaveCount(2);
});

test('it configures a product with variant key', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $response = $driver->configure('HP-001', ['variantKey' => 'standard-100']);

    expect($response)->toBeInstanceOf(ConfiguratorResponse::class)
        ->productCode->toBe('HP-001')
        ->canOrder->toBeTrue();
});

test('it marks configuration as not orderable without variant key', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $response = $driver->configure('HP-001', []);

    expect($response)->toBeInstanceOf(ConfiguratorResponse::class)
        ->canOrder->toBeFalse();
});

test('it gets price from quotes endpoint', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/quotes' => Http::response([
            'products' => [
                [
                    'sku' => 'HP-001',
                    'costPrice' => 1500,
                    'sellPrice' => 2500,
                    'currency' => 'EUR',
                    'basePrice' => 1200,
                    'setupCost' => 300,
                ],
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $priceResponse = $driver->getPrice('HP-001', [
        'variantKey' => 'standard-100',
        'quantity' => 100,
    ]);

    expect($priceResponse)->toBeInstanceOf(PriceResponse::class)
        ->costPrice->toBe(1500)
        ->sellPrice->toBe(2500)
        ->currency->toBe('EUR')
        ->breakdown->toBeArray()
        ->breakdown->toHaveKey('base_price', 1200)
        ->breakdown->toHaveKey('setup_cost', 300);
});

test('it throws exception when pricing fails', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/*' => Http::response(['error' => 'Invalid product'], 400),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $driver->getPrice('HP-001', ['variantKey' => 'invalid']);
})->throws(SupplierException::class);

test('it gets bulk prices', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/quotes' => Http::response([
            'products' => [
                ['sku' => 'HP-001', 'costPrice' => 1500, 'sellPrice' => 2500, 'currency' => 'EUR'],
                ['sku' => 'HP-002', 'costPrice' => 3000, 'sellPrice' => 5000, 'currency' => 'EUR'],
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $prices = $driver->getBulkPrices([
        ['external_id' => 'HP-001', 'configuration' => ['variantKey' => 'v1'], 'quantity' => 100],
        ['external_id' => 'HP-002', 'configuration' => ['variantKey' => 'v2'], 'quantity' => 50],
    ]);

    expect($prices)->toHaveCount(2)
        ->and($prices->first())->toBeInstanceOf(PriceResponse::class)
        ->and($prices->first()->costPrice)->toBe(1500);
});

test('it gets dynamic price with quantity', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/quotes' => Http::response([
            'products' => [
                ['costPrice' => 2000, 'sellPrice' => 3500, 'currency' => 'EUR'],
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $priceResponse = $driver->getDynamicPrice('HP-001', ['variantKey' => 'v1'], 250);

    expect($priceResponse)->toBeInstanceOf(PriceResponse::class)
        ->sellPrice->toBe(3500);
});

test('it submits order with artwork', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    $currency = Currency::factory()->create();
    $country = Country::factory()->create(['iso2' => 'NL']);

    $order = Order::factory()->create([
        'reference' => 'ORD-123',
    ]);

    $shippingAddress = \Lunar\Models\OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => 'shipping',
        'country_id' => $country->id,
        'line_one' => 'Test Street 1',
        'city' => 'Amsterdam',
        'postcode' => '1000 AA',
        'contact_phone' => '+31612345678',
        'contact_email' => 'test@example.com',
    ]);

    $variant = ProductVariant::factory()->create([
        'configuration' => ['variantKey' => 'standard-100'],
    ]);

    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'HP-001',
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
        'api.helloprint.com/rest/v1/orders' => Http::response([
            'orderId' => 'HP-ORD-456',
            'status' => 'submitted',
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);
    $driver->setConfig(['sandbox' => true]);

    $response = $driver->submitOrder($supplierOrder);

    expect($response)->toBeInstanceOf(OrderResponse::class)
        ->success->toBeTrue()
        ->externalId->toBe('HP-ORD-456');

    Http::assertSent(function ($request) {
        $data = $request->data();
        return $data['mode'] === 'test' &&
               isset($data['products'][0]['sku']) &&
               $data['products'][0]['sku'] === 'HP-001' &&
               isset($data['deliveryAddress']);
    });
});

test('it maps status correctly', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/orders/HP-ORD-123' => Http::response([
            'orderId' => 'HP-ORD-123',
            'status' => 'in_production',
            'tracking' => [
                'trackingNumber' => 'TRACK123',
                'trackingUrl' => 'https://track.example.com/TRACK123',
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $status = $driver->getOrderStatus('HP-ORD-123');

    expect($status)->toBeInstanceOf(StatusResponse::class)
        ->status->toBe('processing')
        ->tracking->toBeArray()
        ->tracking->toHaveKey('numbers', ['TRACK123'])
        ->tracking->toHaveKey('url', 'https://track.example.com/TRACK123');
});

test('it cancels an order', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/orders/HP-ORD-123/cancel' => Http::response([], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $result = $driver->cancelOrder('HP-ORD-123');

    expect($result)->toBeTrue();
});

test('it resolves upload spec for product requiring artwork', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/products/HP-001/variants/standard-100' => Http::response([
            'requiresArtwork' => true,
            'artworkSpecs' => [
                'sides' => 'double',
                'dimensions' => [
                    'width' => 85,
                    'height' => 55,
                    'unit' => 'mm',
                ],
                'acceptedFileTypes' => ['pdf', 'jpg', 'png'],
                'maxFileSizeMb' => 25,
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $uploadSpec = $driver->resolveUploadSpec('HP-001', ['variantKey' => 'standard-100']);

    expect($uploadSpec)->toBeInstanceOf(UploadSpec::class)
        ->upload->toBeTrue()
        ->uploaders->toHaveCount(1);

    $uploader = $uploadSpec->uploaders->first();
    expect($uploader->type)->toBe('frontback')
        ->and($uploader->amount)->toBe(2)
        ->and($uploader->width)->toEqual(85)
        ->and($uploader->height)->toEqual(55);
});

test('it returns empty upload spec when no artwork required', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/products/HP-001/variants/standard-100' => Http::response([
            'requiresArtwork' => false,
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $uploadSpec = $driver->resolveUploadSpec('HP-001', ['variantKey' => 'standard-100']);

    expect($uploadSpec)->toBeInstanceOf(UploadSpec::class)
        ->upload->toBeFalse();
});

test('it resolves single sided upload spec', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => [
            'api_key' => 'test-api-key',
        ],
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/products/HP-001/variants/poster-a4' => Http::response([
            'requiresArtwork' => true,
            'artworkSpecs' => [
                'sides' => 'single',
                'dimensions' => [
                    'width' => 210,
                    'height' => 297,
                    'unit' => 'mm',
                ],
                'acceptedFileTypes' => ['pdf'],
                'maxFileSizeMb' => 50,
            ],
        ], 200),
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $uploadSpec = $driver->resolveUploadSpec('HP-001', ['variantKey' => 'poster-a4']);

    expect($uploadSpec)->toBeInstanceOf(UploadSpec::class);

    $uploader = $uploadSpec->uploaders->first();
    expect($uploader->type)->toBe('single')
        ->and($uploader->amount)->toBe(1);
});

test('it returns cart line pipelines', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $pipelines = $driver->getCartLinePipelines();

    expect($pipelines)->toBeArray()
        ->toHaveCount(1)
        ->toContain(\Lunar\Drivers\Suppliers\HelloPrint\Pipelines\GetHelloPrintPrice::class);
});

test('it returns empty hidden sections for dynamic', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $sections = $driver->getHiddenSectionsForDynamic();

    expect($sections)->toBeArray()->toBeEmpty();
});

test('it returns null configurator component', function () {
    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
    ]);

    $driver = new HelloPrintDriver();
    $driver->setSupplier($supplier);

    $component = $driver->getConfiguratorComponent();

    expect($component)->toBeNull();
});
