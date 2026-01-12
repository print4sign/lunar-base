<?php

uses(\Lunar\Tests\Core\TestCase::class)->group('migrations');

use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    // Ensure all migrations are run, including our new ones
    artisan('migrate:fresh');
});

test('print_assets table has supplier order relationship fields', function () {
    $columns = Schema::getColumnListing(prefix_table('print_assets'));

    expect($columns)->toContain('supplier_order_id');
    expect($columns)->toContain('supplier_file_id');
    expect($columns)->toContain('supplier_status');
    expect($columns)->toContain('validation_errors');
});

test('print_assets table has supplier_order_id index', function () {
    $indexes = collect(Schema::getIndexes(prefix_table('print_assets')))
        ->pluck('columns')
        ->map(fn ($cols) => implode(',', $cols));

    expect($indexes->contains('supplier_order_id'))->toBeTrue();
});

test('print_assets extension migration can rollback', function () {
    $migrationFile = __DIR__.'/../../../../packages/core/database/migrations/2026_01_12_100001_extend_print_assets_table.php';

    expect(file_exists($migrationFile))->toBeTrue();

    artisan('migrate:rollback', [
        '--realpath' => $migrationFile,
    ]);

    $columns = Schema::getColumnListing(prefix_table('print_assets'));

    // Verify new columns are removed
    expect($columns)->not->toContain('supplier_order_id');
    expect($columns)->not->toContain('supplier_file_id');
    expect($columns)->not->toContain('supplier_status');
    expect($columns)->not->toContain('validation_errors');

    // Re-run migration
    artisan('migrate', [
        '--realpath' => $migrationFile,
    ]);
});
