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
        Supplier::updateOrCreate(
            ['handle' => 'internal'],
            [
                'name' => 'Internal Fulfillment',
                'driver' => 'internal',
                'capabilities' => ['ordering', 'tracking'],
                'enabled' => true,
                'priority' => 0, // Lowest priority - only use if no other supplier available
                'meta' => [
                    'description' => 'In-house fulfillment for products not sourced through external suppliers.',
                    'cancellation_window_hours' => 72,
                    'cancellation_allowed_statuses' => ['pending', 'submitted', 'processing'],
                ],
            ]
        );
    }
}
