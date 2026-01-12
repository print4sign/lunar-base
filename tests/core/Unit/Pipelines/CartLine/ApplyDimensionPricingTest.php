<?php

uses(\Lunar\Tests\Core\TestCase::class);

use Lunar\Base\Enums\UnitCode;
use Lunar\DataTypes\Price as DataTypesPrice;
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;
use Lunar\Pipelines\Cart\CalculateLines;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('dimension pricing is skipped when no calculated_size in meta', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 1,
        'unit_code' => UnitCode::SQUARE_METER,
    ]);

    Price::factory()->create([
        'price' => 2500, // €25.00/m²
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // No meta with calculated_size
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 1,
        'meta' => [],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Price should remain at base unit price (€25.00)
    expect($line->unitPrice)->toBeInstanceOf(DataTypesPrice::class);
    expect($line->unitPrice->value)->toEqual(2500);
});

test('dimension pricing applies calculated size for area units', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 1,
        'unit_code' => UnitCode::SQUARE_METER,
    ]);

    Price::factory()->create([
        'price' => 2500, // €25.00/m²
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // 100cm × 200cm = 2.0 m²
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 1,
        'meta' => [
            'dimensions' => [
                'width' => 100,
                'height' => 200,
            ],
            'calculated_size' => 2.0,
            'size_unit' => 'm2',
        ],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Price should be €25.00/m² × 2.0 m² = €50.00
    expect($line->unitPrice)->toBeInstanceOf(DataTypesPrice::class);
    expect($line->unitPrice->value)->toEqual(5000);
});

test('dimension pricing applies calculated size for linear units', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 1,
        'unit_code' => UnitCode::METER,
    ]);

    Price::factory()->create([
        'price' => 1000, // €10.00/m
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // 5.5 meters
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 1,
        'meta' => [
            'dimensions' => [
                'length' => 5.5,
            ],
            'calculated_size' => 5.5,
            'size_unit' => 'm',
        ],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Price should be €10.00/m × 5.5 m = €55.00
    expect($line->unitPrice)->toBeInstanceOf(DataTypesPrice::class);
    expect($line->unitPrice->value)->toEqual(5500);
});

test('dimension pricing works with quantity greater than 1', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 1,
        'unit_code' => UnitCode::SQUARE_METER,
    ]);

    Price::factory()->create([
        'price' => 2500, // €25.00/m²
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // 100cm × 200cm = 2.0 m², quantity of 3
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 3, // 3 items at 2.0 m² each
        'meta' => [
            'dimensions' => [
                'width' => 100,
                'height' => 200,
            ],
            'calculated_size' => 2.0,
            'size_unit' => 'm2',
        ],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Unit price should be €25.00/m² × 2.0 m² = €50.00 per item
    expect($line->unitPrice->value)->toEqual(5000);

    // Subtotal should be €50.00 × 3 = €150.00
    expect($line->subTotal->value)->toEqual(15000);
});

test('dimension pricing is skipped for non dimensional unit codes', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 1,
        'unit_code' => UnitCode::PIECE, // Non-dimensional
    ]);

    Price::factory()->create([
        'price' => 1000, // €10.00/pc
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // Even with calculated_size in meta, PIECE should not apply dimension pricing
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 1,
        'meta' => [
            'calculated_size' => 2.0, // This should be ignored for PIECE
        ],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Price should remain at €10.00 (not multiplied)
    expect($line->unitPrice->value)->toEqual(1000);
});

test('dimension pricing handles unit quantity greater than 1', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    // Price is €100 for 10 m² (€10/m²)
    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 10,
        'unit_code' => UnitCode::SQUARE_METER,
    ]);

    Price::factory()->create([
        'price' => 10000, // €100 for 10 m² = €10/m²
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // 100cm × 200cm = 2.0 m²
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 1,
        'meta' => [
            'dimensions' => [
                'width' => 100,
                'height' => 200,
            ],
            'calculated_size' => 2.0,
            'size_unit' => 'm2',
        ],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Unit price: €100/10m² = €10/m² × 2.0 m² = €20.00
    expect($line->unitPrice->value)->toEqual(2000);
});

test('dimension pricing skips zero or negative calculated size', function () {
    $currency = Currency::factory()->create([
        'decimal_places' => 2,
    ]);

    $cart = Cart::factory()->create([
        'currency_id' => $currency->id,
    ]);

    $purchasable = ProductVariant::factory()->create([
        'unit_quantity' => 1,
        'unit_code' => UnitCode::SQUARE_METER,
    ]);

    Price::factory()->create([
        'price' => 2500,
        'currency_id' => $currency->id,
        'min_quantity' => 1,
        'priceable_type' => $purchasable->getMorphClass(),
        'priceable_id' => $purchasable->id,
    ]);

    // Zero calculated size should be skipped
    $cart->lines()->create([
        'purchasable_type' => $purchasable->getMorphClass(),
        'purchasable_id' => $purchasable->id,
        'quantity' => 1,
        'meta' => [
            'calculated_size' => 0,
        ],
    ]);

    $cart = app(CalculateLines::class)->handle($cart, fn ($cart) => $cart);

    $line = $cart->lines->first();

    // Price should remain at base (€25.00)
    expect($line->unitPrice->value)->toEqual(2500);
});
