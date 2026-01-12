<?php

uses(\Lunar\Tests\Core\TestCase::class)->group('migrations');

use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\artisan;

require_once __DIR__.'/Helpers.php';

beforeEach(function () {
    // Ensure all migrations are run, including our new ones
    artisan('migrate:fresh');
});

test('supplier_orders table has all cancellation fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('cancellable');
    expect($columns)->toContain('cancellation_deadline');
    expect($columns)->toContain('cancellation_requested_at');
    expect($columns)->toContain('cancellation_requested_by');
    expect($columns)->toContain('cancellation_reason');
    expect($columns)->toContain('cancelled_at');
    expect($columns)->toContain('cancelled_by');
    expect($columns)->toContain('cancellation_fee');
});

test('supplier_orders table has all cost tracking fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('estimated_cost_price');
    expect($columns)->toContain('actual_cost_price');
    expect($columns)->toContain('supplier_shipping_cost');
    expect($columns)->toContain('supplier_additional_costs');
    expect($columns)->toContain('supplier_total_cost');
});

test('supplier_orders table has all revenue tracking fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('order_line_unit_price');
    expect($columns)->toContain('order_line_total');
    expect($columns)->toContain('profit_margin_cents');
    expect($columns)->toContain('profit_margin_percentage');
});

test('supplier_orders table has all refund tracking fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('refund_amount');
    expect($columns)->toContain('refund_issued_at');
    expect($columns)->toContain('refund_reason');
});

test('supplier_orders table has all approval workflow fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('requires_approval');
    expect($columns)->toContain('approved_by');
    expect($columns)->toContain('approved_at');
    expect($columns)->toContain('approval_notes');
    expect($columns)->toContain('rejected_reason');
});

test('supplier_orders table has all artwork management fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('artwork_files');
    expect($columns)->toContain('artwork_status');
    expect($columns)->toContain('artwork_approval_deadline');
});

test('supplier_orders table has all delivery tracking fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('estimated_delivery_date');
    expect($columns)->toContain('actual_delivery_date');
    expect($columns)->toContain('tracking_numbers');
    expect($columns)->toContain('tracking_url');
});

test('supplier_orders table has external status fields', function () {
    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    expect($columns)->toContain('substatus');
    expect($columns)->toContain('external_status');
});

test('supplier_orders table has required indexes', function () {
    $indexes = collect(Schema::getIndexes(prefix_table('supplier_orders')))
        ->pluck('columns')
        ->map(fn ($cols) => implode(',', $cols));

    // Check for composite indexes
    expect($indexes->contains(fn ($idx) => str_contains($idx, 'status') && str_contains($idx, 'requires_approval')))->toBeTrue();
    expect($indexes->contains(fn ($idx) => str_contains($idx, 'supplier_id') && str_contains($idx, 'status')))->toBeTrue();
    expect($indexes->contains(fn ($idx) => str_contains($idx, 'order_id') && str_contains($idx, 'status')))->toBeTrue();

    // Check for single column indexes
    expect($indexes->contains('cancellation_deadline'))->toBeTrue();
    expect($indexes->contains('estimated_delivery_date'))->toBeTrue();
});

test('supplier_orders extension migration can rollback', function () {
    $migrationFile = __DIR__.'/../../../../packages/core/database/migrations/2026_01_12_100000_extend_supplier_orders_table.php';

    expect(file_exists($migrationFile))->toBeTrue();

    artisan('migrate:rollback', [
        '--realpath' => $migrationFile,
    ]);

    $columns = Schema::getColumnListing(prefix_table('supplier_orders'));

    // Verify new columns are removed
    expect($columns)->not->toContain('cancellable');
    expect($columns)->not->toContain('requires_approval');
    expect($columns)->not->toContain('artwork_status');

    // Re-run migration
    artisan('migrate', [
        '--realpath' => $migrationFile,
    ]);
});

test('user foreign keys have nullOnDelete constraint', function () {
    // Get the foreign key definitions for the supplier_orders table using Schema::getForeignKeys
    $foreignKeys = collect(Schema::getForeignKeys(prefix_table('supplier_orders')));

    // Check that cancellation_requested_by has nullOnDelete (SET NULL)
    $cancellationRequestedByFk = $foreignKeys->first(fn ($fk) =>
        in_array('cancellation_requested_by', $fk['columns'])
    );
    expect($cancellationRequestedByFk)->not->toBeNull();
    expect($cancellationRequestedByFk['on_delete'])->toBe('set null');

    // Check that cancelled_by has nullOnDelete (SET NULL)
    $cancelledByFk = $foreignKeys->first(fn ($fk) =>
        in_array('cancelled_by', $fk['columns'])
    );
    expect($cancelledByFk)->not->toBeNull();
    expect($cancelledByFk['on_delete'])->toBe('set null');

    // Check that approved_by has nullOnDelete (SET NULL)
    $approvedByFk = $foreignKeys->first(fn ($fk) =>
        in_array('approved_by', $fk['columns'])
    );
    expect($approvedByFk)->not->toBeNull();
    expect($approvedByFk['on_delete'])->toBe('set null');
});
