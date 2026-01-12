<?php

namespace Lunar\Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Supplier;

class InternalSupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::firstOrCreate(
            ['handle' => 'internal'],
            [
                'name' => 'Internal Fulfillment',
                'driver' => 'internal',
                'credentials' => [],
                'capabilities' => ['ordering', 'tracking'],
                'enabled' => true,
                'priority' => 0, // Lowest priority - only use if no other supplier available
                'meta' => [
                    'description' => 'Products fulfilled internally by our warehouse',
                    'cancellation_window' => 72,
                    'cancellation_allowed_statuses' => ['pending', 'approved', 'submitted', 'processing'],
                ],
            ]
        );
    }
}
