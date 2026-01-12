<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class SupplierProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing existing supplier products...');
        SupplierProduct::truncate();

        $this->command->info('Syncing supplier products from APIs...');

        // Sync real products from Probo API
        $this->syncProbo();

        // Sync real products from HelloPrint API
        $this->syncHelloPrint();

        // Sync real products from print.com API
        $this->syncPrintCom();

        // Seed Internal demo products (no API)
        $this->seedInternalProducts();

        $this->command->info('✅ Supplier products synced successfully!');
    }

    protected function syncProbo(): void
    {
        $supplier = Supplier::where('handle', 'probo')->first();

        if (! $supplier) {
            $this->command->warn('Probo supplier not found. Run SuppliersSeeder first.');

            return;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('lunar:supplier:sync', [
                'supplier' => 'probo',
                '--sync' => true,
                '--import' => true,
                '--skip-existing' => false,
                '--no-prices' => false,
            ], $this->command->getOutput());

            $count = SupplierProduct::where('supplier_id', $supplier->id)->count();
            $this->command->info("   - Probo: {$count} products synced from API");
        } catch (\Exception $e) {
            $this->command->error("   - Probo sync failed: {$e->getMessage()}");
        }
    }

    protected function syncHelloPrint(): void
    {
        $supplier = Supplier::where('handle', 'helloprint')->first();

        if (! $supplier) {
            $this->command->warn('HelloPrint supplier not found. Run SuppliersSeeder first.');

            return;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('lunar:supplier:sync', [
                'supplier' => 'helloprint',
                '--sync' => true,
                '--import' => true,
                '--skip-existing' => false,
                '--no-prices' => false,
            ], $this->command->getOutput());

            $count = SupplierProduct::where('supplier_id', $supplier->id)->count();
            $this->command->info("   - HelloPrint: {$count} products synced from API");
        } catch (\Exception $e) {
            $this->command->error("   - HelloPrint sync failed: {$e->getMessage()}");
        }
    }

    protected function syncPrintCom(): void
    {
        $supplier = Supplier::where('handle', 'printcom')->first();

        if (! $supplier) {
            $this->command->warn('print.com supplier not found. Run SuppliersSeeder first.');

            return;
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('lunar:supplier:sync', [
                'supplier' => 'printcom',
                '--sync' => true,
                '--import' => true,
                '--skip-existing' => false,
                '--no-prices' => false,
            ], $this->command->getOutput());

            $count = SupplierProduct::where('supplier_id', $supplier->id)->count();
            $this->command->info("   - print.com: {$count} products synced from API");
        } catch (\Exception $e) {
            $this->command->error("   - print.com sync failed: {$e->getMessage()}");
        }
    }

    protected function seedInternalProducts(): void
    {
        $supplier = Supplier::where('handle', 'internal')->first();

        if (! $supplier) {
            $this->command->warn('Internal supplier not found. Run SuppliersSeeder first.');

            return;
        }

        $products = [
            [
                'external_id' => 'INT-VINYL-BANNER',
                'external_name' => 'Vinyl Banner Custom Size',
                'external_data' => [
                    'description' => 'Custom-sized vinyl banners for indoor and outdoor use. Durable, weather-resistant material.',
                    'specifications' => [
                        'material' => 'PVC Vinyl 440gsm',
                        'finish' => 'Matt',
                        'customizable' => true,
                        'color' => 'Full Color',
                        'weatherproof' => true,
                    ],
                    'categories' => ['banners', 'signage', 'custom'],
                ],
            ],
            [
                'external_id' => 'INT-CANVAS-PRINT',
                'external_name' => 'Canvas Print Custom',
                'external_data' => [
                    'description' => 'High-quality canvas prints with wooden frame. Perfect for photos, artwork, and decorative prints.',
                    'specifications' => [
                        'material' => 'Canvas 340gsm',
                        'frame' => 'Wooden stretcher bars',
                        'customizable' => true,
                        'color' => 'Full Color',
                    ],
                    'categories' => ['canvas', 'prints', 'custom'],
                ],
            ],
        ];

        foreach ($products as $productData) {
            SupplierProduct::updateOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'external_id' => $productData['external_id'],
                ],
                $productData
            );
        }

        $this->command->info('   - Internal: 2 products seeded');
    }
}
