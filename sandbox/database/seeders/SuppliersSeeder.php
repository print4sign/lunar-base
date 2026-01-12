<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Supplier;

class SuppliersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Probo',
                'handle' => 'probo',
                'driver' => 'probo',
                'enabled' => true,
                'priority' => 70,
                'credentials' => [
                    'api_key' => env('PROBO_API_KEY', 'demo-key'),
                    'api_url' => env('PROBO_API_URL', 'https://api.probo.nl'),
                ],
                'capabilities' => [
                    'catalog_sync' => true,
                    'pricing' => true,
                    'ordering' => true,
                    'tracking' => true,
                    'configurator' => true,
                    'cancellation_window_hours' => 24,
                ],
                'meta' => [
                    'cancellation_allowed_statuses' => ['pending', 'approved', 'submitted'],
                    'default_shipping_method' => 'standard',
                ],
            ],
            [
                'name' => 'HelloPrint',
                'handle' => 'helloprint',
                'driver' => 'helloprint',
                'enabled' => true,
                'priority' => 60,
                'credentials' => [
                    'api_key' => env('HELLOPRINT_API_KEY', 'demo-key'),
                ],
                'capabilities' => [
                    'catalog_sync' => true,
                    'pricing' => true,
                    'ordering' => true,
                    'tracking' => true,
                    'cancellation_window_hours' => 48,
                ],
                'meta' => [
                    'cancellation_allowed_statuses' => ['pending', 'approved'],
                    'default_shipping_method' => 'express',
                ],
            ],
            [
                'name' => 'print.com',
                'handle' => 'printcom',
                'driver' => 'printcom',
                'enabled' => true,
                'priority' => 50,
                'credentials' => [
                    'api_key' => env('PRINTCOM_API_KEY', 'demo-key'),
                ],
                'capabilities' => [
                    'catalog_sync' => true,
                    'pricing' => true,
                    'ordering' => true,
                    'tracking' => true,
                    'cancellation_window_hours' => 24,
                ],
                'meta' => [
                    'cancellation_allowed_statuses' => ['pending'],
                    'default_shipping_method' => 'standard',
                ],
            ],
            [
                'name' => 'Internal Production',
                'handle' => 'internal',
                'driver' => 'internal',
                'enabled' => true,
                'priority' => 80,
                'credentials' => [],
                'capabilities' => [
                    'ordering' => true,
                    'tracking' => true,
                ],
                'meta' => [
                    'cancellation_allowed_statuses' => ['pending', 'approved', 'submitted', 'processing'],
                    'default_shipping_method' => 'pickup',
                ],
            ],
            [
                'name' => 'Offline (Manual)',
                'handle' => 'offline',
                'driver' => 'offline',
                'enabled' => true,
                'priority' => 10,
                'credentials' => [],
                'capabilities' => [
                    'ordering' => true,
                ],
                'meta' => [
                    'cancellation_allowed_statuses' => ['pending'],
                    'requires_manual_fulfillment' => true,
                ],
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::updateOrCreate(
                ['handle' => $supplierData['handle']],
                $supplierData
            );
        }

        $this->command->info('✅ Suppliers seeded successfully!');
        $this->command->info('   - Probo (priority 70)');
        $this->command->info('   - HelloPrint (priority 60)');
        $this->command->info('   - print.com (priority 50)');
        $this->command->info('   - Internal Production (priority 80)');
        $this->command->info('   - Offline (priority 10)');
    }
}
