<?php

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use Illuminate\Support\Facades\Http;
use Lunar\DataTypes\Price;
use Lunar\Drivers\Suppliers\HelloPrint\Pipelines\GetHelloPrintPrice;
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

test('it skips non helloprint variants', function () {
    $currency = Currency::factory()->create();
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    $supplier = Supplier::factory()->create(['driver' => 'probo']); // Different driver

    $variant = ProductVariant::factory()->create();
    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'TEST-001',
    ]);
    $variant->supplierProduct()->associate($supplierProduct);
    $variant->save();

    $cartLine = $cart->lines()->create([
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 1,
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));
    $result = $pipeline->handle($cartLine, fn ($line) => $line);

    expect($result)->toBe($cartLine)
        ->and($cartLine->unit_price)->toBeNull();
});

test('it skips variants without supplier product', function () {
    $currency = Currency::factory()->create();
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    $variant = ProductVariant::factory()->create();

    $cartLine = $cart->lines()->create([
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 1,
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));
    $result = $pipeline->handle($cartLine, fn ($line) => $line);

    expect($result)->toBe($cartLine)
        ->and($cartLine->unit_price)->toBeNull();
});

test('it skips non dynamic variants', function () {
    $currency = Currency::factory()->create();
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => ['api_key' => 'test-key'],
    ]);

    $variant = ProductVariant::factory()->create([
        'is_dynamic' => false, // Not dynamic
    ]);

    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'HP-001',
    ]);

    $variant->supplierProduct()->associate($supplierProduct);
    $variant->save();

    $cartLine = $cart->lines()->create([
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 1,
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));
    $result = $pipeline->handle($cartLine, fn ($line) => $line);

    expect($result)->toBe($cartLine)
        ->and($cartLine->unit_price)->toBeNull();
});

test('it fetches price for dynamic helloprint variant', function () {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => ['api_key' => 'test-key'],
    ]);

    $variant = ProductVariant::factory()->create([
        'is_dynamic' => true,
        'configuration' => ['variantKey' => 'standard-100'],
    ]);

    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'HP-001',
    ]);

    $variant->supplierProduct()->associate($supplierProduct);
    $variant->save();

    $cartLine = $cart->lines()->create([
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 100,
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/quotes' => Http::response([
            'products' => [
                [
                    'sku' => 'HP-001',
                    'costPrice' => 5000, // €50.00
                    'sellPrice' => 7500, // €75.00
                    'currency' => 'EUR',
                ],
            ],
        ], 200),
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));
    $result = $pipeline->handle($cartLine, fn ($line) => $line);

    expect($result)->toBe($cartLine)
        ->and($cartLine->unit_price)->toBe(7500)
        ->and($cartLine->meta)->toHaveKey('supplier_pricing')
        ->and($cartLine->meta['supplier_pricing']['cost_price'])->toBe(5000)
        ->and($cartLine->meta['supplier_pricing']['sell_price'])->toBe(7500)
        ->and($cartLine->meta['supplier_pricing']['currency'])->toBe('EUR');
});

test('it handles pricing errors gracefully', function () {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => ['api_key' => 'test-key'],
    ]);

    $variant = ProductVariant::factory()->create([
        'is_dynamic' => true,
        'configuration' => ['variantKey' => 'standard-100'],
    ]);

    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'HP-001',
    ]);

    $variant->supplierProduct()->associate($supplierProduct);
    $variant->save();

    $cartLine = $cart->lines()->create([
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 100,
    ]);

    Http::fake([
        'api.helloprint.com/*' => Http::response(['error' => 'API Error'], 500),
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));
    $result = $pipeline->handle($cartLine, fn ($line) => $line);

    // Should continue without setting price
    expect($result)->toBe($cartLine)
        ->and($cartLine->unit_price)->toBeNull();
});

test('it stores supplier pricing in cart line meta', function () {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    $supplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => ['api_key' => 'test-key'],
    ]);

    $variant = ProductVariant::factory()->create([
        'is_dynamic' => true,
        'configuration' => ['variantKey' => 'premium-500'],
    ]);

    $supplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $supplier->id,
        'external_id' => 'HP-002',
    ]);

    $variant->supplierProduct()->associate($supplierProduct);
    $variant->save();

    $cartLine = $cart->lines()->create([
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->id,
        'quantity' => 500,
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/quotes' => Http::response([
            'products' => [
                [
                    'sku' => 'HP-002',
                    'costPrice' => 15000,
                    'sellPrice' => 20000,
                    'currency' => 'EUR',
                    'basePrice' => 14000,
                    'setupCost' => 1000,
                ],
            ],
        ], 200),
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));
    $result = $pipeline->handle($cartLine, fn ($line) => $line);

    $meta = $cartLine->meta;

    expect($meta)->toHaveKey('supplier_pricing')
        ->and($meta['supplier_pricing'])->toBeArray()
        ->and($meta['supplier_pricing']['cost_price'])->toBe(15000)
        ->and($meta['supplier_pricing']['sell_price'])->toBe(20000)
        ->and($meta['supplier_pricing']['currency'])->toBe('EUR')
        ->and($meta['supplier_pricing'])->toHaveKey('fetched_at');
});

test('it only processes helloprint dynamic variants', function () {
    $currency = Currency::factory()->create(['code' => 'EUR']);
    $cart = Cart::factory()->create(['currency_id' => $currency->id]);

    // Create a Probo variant (should be skipped)
    $proboSupplier = Supplier::factory()->create([
        'driver' => 'probo',
        'credentials' => ['api_key' => 'probo-key'],
    ]);

    $proboVariant = ProductVariant::factory()->create([
        'is_dynamic' => true,
    ]);

    $proboSupplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $proboSupplier->id,
        'external_id' => 'PROBO-001',
    ]);

    $proboVariant->supplierProduct()->associate($proboSupplierProduct);
    $proboVariant->save();

    $proboCartLine = $cart->lines()->create([
        'purchasable_type' => $proboVariant->getMorphClass(),
        'purchasable_id' => $proboVariant->id,
        'quantity' => 1,
    ]);

    // Create HelloPrint variant (should be processed)
    $helloSupplier = Supplier::factory()->create([
        'driver' => 'helloprint',
        'credentials' => ['api_key' => 'hello-key'],
    ]);

    $helloVariant = ProductVariant::factory()->create([
        'is_dynamic' => true,
        'configuration' => ['variantKey' => 'test-variant'],
    ]);

    $helloSupplierProduct = SupplierProduct::factory()->create([
        'supplier_id' => $helloSupplier->id,
        'external_id' => 'HP-001',
    ]);

    $helloVariant->supplierProduct()->associate($helloSupplierProduct);
    $helloVariant->save();

    $helloCartLine = $cart->lines()->create([
        'purchasable_type' => $helloVariant->getMorphClass(),
        'purchasable_id' => $helloVariant->id,
        'quantity' => 100,
    ]);

    Http::fake([
        'api.helloprint.com/rest/v1/quotes' => Http::response([
            'products' => [
                [
                    'sku' => 'HP-001',
                    'costPrice' => 5000,
                    'sellPrice' => 7500,
                    'currency' => 'EUR',
                ],
            ],
        ], 200),
    ]);

    $pipeline = new GetHelloPrintPrice(app(\Lunar\Managers\SupplierManager::class));

    // Process Probo line (should be skipped)
    $proboResult = $pipeline->handle($proboCartLine, fn ($line) => $line);
    expect($proboResult->unit_price)->toBeNull();

    // Process HelloPrint line (should be priced)
    $helloResult = $pipeline->handle($helloCartLine, fn ($line) => $line);
    expect($helloResult->unit_price)->toBe(7500);
});
