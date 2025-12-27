<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Lunar\Models\Supplier;

class SupplierProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::enabled()->get();

        if ($suppliers->isEmpty()) {
            $this->command->warn('No enabled suppliers found. Run DemoDataSeeder first.');

            return;
        }

        foreach ($suppliers as $supplier) {
            if (! $supplier->supports('catalog_sync')) {
                $this->command->info("Skipping {$supplier->name} (no catalog_sync capability)");

                continue;
            }

            $this->command->info("Syncing {$supplier->name} catalog...");

            Artisan::call('lunar:supplier:sync', [
                'supplier' => $supplier->handle,
                '--sync' => true,
                '--import' => true,
                '--skip-existing' => true,
                '--no-prices' => false,
            ], $this->command->getOutput());
        }

        $this->command->info('Supplier products synced and imported!');
    }
}
