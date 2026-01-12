<?php

use Lunar\Database\Seeders\InternalSupplierSeeder;
use Lunar\Models\Supplier;

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('seeder creates internal supplier with correct attributes', function () {
    $seeder = new InternalSupplierSeeder();
    $seeder->run();

    $supplier = Supplier::where('handle', 'internal')->first();

    expect($supplier)->not->toBeNull()
        ->and($supplier->name)->toBe('Internal Fulfillment')
        ->and($supplier->handle)->toBe('internal')
        ->and($supplier->driver)->toBe('internal')
        ->and($supplier->credentials)->toBeArray()
        ->toBeEmpty()
        ->and($supplier->capabilities)->toBeArray()
        ->toContain('ordering')
        ->toContain('tracking')
        ->and($supplier->enabled)->toBeTrue()
        ->and($supplier->priority)->toBe(0);
});

test('seeder sets correct meta information', function () {
    $seeder = new InternalSupplierSeeder();
    $seeder->run();

    $supplier = Supplier::where('handle', 'internal')->first();

    expect($supplier->meta)->toHaveKey('description', 'Products fulfilled internally by our warehouse')
        ->toHaveKey('cancellation_window', 72)
        ->toHaveKey('cancellation_allowed_statuses')
        ->and($supplier->meta['cancellation_allowed_statuses'])->toContain('pending')
        ->toContain('approved')
        ->toContain('submitted')
        ->toContain('processing');
});

test('seeder is idempotent - running multiple times does not create duplicates', function () {
    $seeder = new InternalSupplierSeeder();
    $seeder->run();
    $seeder->run();
    $seeder->run();

    $count = Supplier::where('handle', 'internal')->count();

    expect($count)->toBe(1);
});

test('seeder does not update existing internal supplier', function () {
    // Create initial supplier
    Supplier::create([
        'handle' => 'internal',
        'name' => 'Old Name',
        'driver' => 'internal',
        'capabilities' => ['manual'],
        'enabled' => false,
        'priority' => 100,
    ]);

    $seeder = new InternalSupplierSeeder();
    $seeder->run();

    $supplier = Supplier::where('handle', 'internal')->first();

    // firstOrCreate should NOT update existing records
    expect($supplier->name)->toBe('Old Name')
        ->and($supplier->enabled)->toBeFalse()
        ->and($supplier->priority)->toBe(100)
        ->and($supplier->capabilities)->toContain('manual');
});
