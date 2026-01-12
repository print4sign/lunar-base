<?php

uses(\Lunar\Tests\Core\TestCase::class);

use Lunar\Base\Enums\UnitCode;

test('unit code has correct cases', function () {
    expect(UnitCode::cases())->toHaveCount(13);

    expect(UnitCode::PIECE->value)->toBe('pc');
    expect(UnitCode::METER->value)->toBe('m');
    expect(UnitCode::SQUARE_METER->value)->toBe('m2');
    expect(UnitCode::CENTIMETER->value)->toBe('cm');
    expect(UnitCode::SQUARE_CENTIMETER->value)->toBe('cm2');
    expect(UnitCode::MILLIMETER->value)->toBe('mm');
    expect(UnitCode::ROLL->value)->toBe('roll');
    expect(UnitCode::SHEET->value)->toBe('sheet');
    expect(UnitCode::KILOGRAM->value)->toBe('kg');
    expect(UnitCode::GRAM->value)->toBe('g');
    expect(UnitCode::SET->value)->toBe('set');
    expect(UnitCode::BOX->value)->toBe('box');
    expect(UnitCode::PACK->value)->toBe('pack');
});

test('unit code returns correct symbols', function () {
    expect(UnitCode::PIECE->symbol())->toBe('pc');
    expect(UnitCode::METER->symbol())->toBe('m');
    expect(UnitCode::SQUARE_METER->symbol())->toBe('m²');
    expect(UnitCode::CENTIMETER->symbol())->toBe('cm');
    expect(UnitCode::SQUARE_CENTIMETER->symbol())->toBe('cm²');
    expect(UnitCode::MILLIMETER->symbol())->toBe('mm');
    expect(UnitCode::ROLL->symbol())->toBe('roll');
    expect(UnitCode::SHEET->symbol())->toBe('sheet');
    expect(UnitCode::KILOGRAM->symbol())->toBe('kg');
    expect(UnitCode::GRAM->symbol())->toBe('g');
    expect(UnitCode::SET->symbol())->toBe('set');
    expect(UnitCode::BOX->symbol())->toBe('box');
    expect(UnitCode::PACK->symbol())->toBe('pack');
});

test('unit code options returns all codes with labels', function () {
    $options = UnitCode::options();

    expect($options)->toBeArray();
    expect($options)->toHaveCount(13);
    expect(array_keys($options))->toBe([
        'pc', 'm', 'm2', 'cm', 'cm2', 'mm', 'roll', 'sheet', 'kg', 'g', 'set', 'box', 'pack',
    ]);
});

test('area unit codes are identified correctly', function () {
    expect(UnitCode::SQUARE_METER->isArea())->toBeTrue();
    expect(UnitCode::SQUARE_CENTIMETER->isArea())->toBeTrue();

    expect(UnitCode::METER->isArea())->toBeFalse();
    expect(UnitCode::CENTIMETER->isArea())->toBeFalse();
    expect(UnitCode::MILLIMETER->isArea())->toBeFalse();
    expect(UnitCode::PIECE->isArea())->toBeFalse();
    expect(UnitCode::ROLL->isArea())->toBeFalse();
});

test('linear unit codes are identified correctly', function () {
    expect(UnitCode::METER->isLinear())->toBeTrue();
    expect(UnitCode::CENTIMETER->isLinear())->toBeTrue();
    expect(UnitCode::MILLIMETER->isLinear())->toBeTrue();

    expect(UnitCode::SQUARE_METER->isLinear())->toBeFalse();
    expect(UnitCode::SQUARE_CENTIMETER->isLinear())->toBeFalse();
    expect(UnitCode::PIECE->isLinear())->toBeFalse();
    expect(UnitCode::ROLL->isLinear())->toBeFalse();
});

test('dimensional unit codes are identified correctly', function () {
    // Area units are dimensional
    expect(UnitCode::SQUARE_METER->isDimensional())->toBeTrue();
    expect(UnitCode::SQUARE_CENTIMETER->isDimensional())->toBeTrue();

    // Linear units are dimensional
    expect(UnitCode::METER->isDimensional())->toBeTrue();
    expect(UnitCode::CENTIMETER->isDimensional())->toBeTrue();
    expect(UnitCode::MILLIMETER->isDimensional())->toBeTrue();

    // Non-dimensional units
    expect(UnitCode::PIECE->isDimensional())->toBeFalse();
    expect(UnitCode::ROLL->isDimensional())->toBeFalse();
    expect(UnitCode::SHEET->isDimensional())->toBeFalse();
    expect(UnitCode::KILOGRAM->isDimensional())->toBeFalse();
    expect(UnitCode::GRAM->isDimensional())->toBeFalse();
    expect(UnitCode::SET->isDimensional())->toBeFalse();
    expect(UnitCode::BOX->isDimensional())->toBeFalse();
    expect(UnitCode::PACK->isDimensional())->toBeFalse();
});

test('dimension type returns correct value', function () {
    expect(UnitCode::SQUARE_METER->getDimensionType())->toBe('area');
    expect(UnitCode::SQUARE_CENTIMETER->getDimensionType())->toBe('area');

    expect(UnitCode::METER->getDimensionType())->toBe('linear');
    expect(UnitCode::CENTIMETER->getDimensionType())->toBe('linear');
    expect(UnitCode::MILLIMETER->getDimensionType())->toBe('linear');

    expect(UnitCode::PIECE->getDimensionType())->toBeNull();
    expect(UnitCode::ROLL->getDimensionType())->toBeNull();
    expect(UnitCode::BOX->getDimensionType())->toBeNull();
});

test('input unit returns correct value', function () {
    // Area units use cm for input
    expect(UnitCode::SQUARE_METER->getInputUnit())->toBe('cm');
    expect(UnitCode::SQUARE_CENTIMETER->getInputUnit())->toBe('cm');

    // Linear units use their native unit
    expect(UnitCode::METER->getInputUnit())->toBe('m');
    expect(UnitCode::CENTIMETER->getInputUnit())->toBe('cm');
    expect(UnitCode::MILLIMETER->getInputUnit())->toBe('mm');

    // Non-dimensional units return empty string
    expect(UnitCode::PIECE->getInputUnit())->toBe('');
    expect(UnitCode::ROLL->getInputUnit())->toBe('');
});

test('calculate size for square meter', function () {
    // 100cm × 200cm = 2.0 m²
    expect(UnitCode::SQUARE_METER->calculateSize(100, 200))->toBe(2.0);

    // 50cm × 50cm = 0.25 m²
    expect(UnitCode::SQUARE_METER->calculateSize(50, 50))->toBe(0.25);

    // 21cm × 29.7cm ≈ 0.06237 m² (A4)
    $size = UnitCode::SQUARE_METER->calculateSize(21, 29.7);
    expect($size)->toBeGreaterThan(0.062)->toBeLessThan(0.063);
});

test('calculate size for square centimeter', function () {
    // 100cm × 200cm = 20000 cm²
    expect(UnitCode::SQUARE_CENTIMETER->calculateSize(100, 200))->toBe(20000.0);

    // 50cm × 50cm = 2500 cm²
    expect(UnitCode::SQUARE_CENTIMETER->calculateSize(50, 50))->toBe(2500.0);
});

test('calculate size for linear units', function () {
    // Linear units just return the input value
    expect(UnitCode::METER->calculateSize(5.5))->toBe(5.5);
    expect(UnitCode::CENTIMETER->calculateSize(150))->toBe(150.0);
    expect(UnitCode::MILLIMETER->calculateSize(2500))->toBe(2500.0);
});

test('calculate size for non dimensional returns 1', function () {
    expect(UnitCode::PIECE->calculateSize(10))->toBe(1.0);
    expect(UnitCode::BOX->calculateSize(10, 20))->toBe(1.0);
    expect(UnitCode::ROLL->calculateSize(5))->toBe(1.0);
});

test('calculate size handles missing second dimension', function () {
    // When height is null for area units, result is 0
    expect(UnitCode::SQUARE_METER->calculateSize(100, null))->toBe(0.0);
    expect(UnitCode::SQUARE_METER->calculateSize(100))->toBe(0.0);
});
