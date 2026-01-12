<?php

namespace Lunar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Lunar\Facades\Suppliers;
use Lunar\Jobs\Suppliers\CheckSupplierProductUpdate;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class CheckSupplierProductUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:supplier:check-updates
                            {supplier? : The supplier handle or ID to check}
                            {--all : Check all enabled suppliers}
                            {--sync : Run synchronously instead of queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for product updates from supplier(s) and handle discontinuations/replacements';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $suppliers = $this->getSuppliers();

        if ($suppliers->isEmpty()) {
            $this->components->error('No suppliers found to check.');

            return self::FAILURE;
        }

        $this->components->info("Checking product updates for {$suppliers->count()} supplier(s)...");

        $totalJobs = 0;

        foreach ($suppliers as $supplier) {
            if (! $supplier->isEnabled()) {
                $this->components->warn("Supplier {$supplier->name} is not enabled, skipping.");

                continue;
            }

            if (! $supplier->supports('catalog_sync')) {
                $this->components->warn("Supplier {$supplier->name} does not support catalog sync, skipping.");

                continue;
            }

            $this->components->twoColumnDetail($supplier->name, $supplier->driver);

            $jobs = $this->processSupplier($supplier);
            $totalJobs += count($jobs);

            // Update the last checked timestamp
            $supplier->setLastProductUpdateCheck(now());
        }

        $this->components->success("Product update check complete. Processed {$totalJobs} products.");

        return self::SUCCESS;
    }

    /**
     * Process a single supplier.
     */
    protected function processSupplier(Supplier $supplier): array
    {
        $driver = Suppliers::supplier($supplier);
        $lastChecked = $supplier->getLastProductUpdateCheck();

        $this->components->bulletList([
            'Last checked: '.($lastChecked ? $lastChecked->diffForHumans() : 'never'),
        ]);

        // Fetch all products from the API
        $products = $driver->getUpdatedProducts($lastChecked);

        $this->components->twoColumnDetail('Products from API', $products->count());

        $jobs = [];
        $discontinued = 0;
        $hasReplacement = 0;

        foreach ($products as $productData) {
            $externalId = $productData['code'] ?? null;

            if (! $externalId) {
                continue;
            }

            // Find or create the supplier product
            $supplierProduct = SupplierProduct::firstOrCreate(
                [
                    'supplier_id' => $supplier->id,
                    'external_id' => $externalId,
                ],
                [
                    'external_name' => $productData['translations']['en']['title']
                        ?? $productData['translations']['nl']['title']
                        ?? $productData['name']
                        ?? $externalId,
                    'external_data' => $productData,
                    'active' => $productData['active'] ?? true,
                    'synced' => true,
                    'last_synced_at' => now(),
                ]
            );

            // Track stats
            if (! ($productData['active'] ?? true)) {
                $discontinued++;
            }
            if (! empty($productData['replaced_by_product'])) {
                $hasReplacement++;
            }

            $job = new CheckSupplierProductUpdate($supplierProduct, $productData);
            $jobs[] = $job;
        }

        $this->components->twoColumnDetail('Discontinued products', $discontinued);
        $this->components->twoColumnDetail('Products with replacements', $hasReplacement);

        if (empty($jobs)) {
            return [];
        }

        if ($this->option('sync')) {
            // Run synchronously
            $bar = $this->output->createProgressBar(count($jobs));
            $bar->start();

            foreach ($jobs as $job) {
                try {
                    dispatch_sync($job);
                    $bar->advance();
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->components->error("Job failed: {$e->getMessage()}");
                }
            }

            $bar->finish();
            $this->newLine();
        } else {
            // Queue the jobs as a batch
            $batch = Bus::batch($jobs)
                ->name("Supplier Product Updates: {$supplier->name}")
                ->allowFailures()
                ->dispatch();

            $this->components->info("Queued batch: {$batch->id}");
        }

        return $jobs;
    }

    /**
     * Get the suppliers to check.
     */
    protected function getSuppliers()
    {
        if ($this->option('all')) {
            return Supplier::enabled()->get();
        }

        $identifier = $this->argument('supplier');

        if (! $identifier) {
            // Interactive selection
            $suppliers = Supplier::enabled()->get();

            if ($suppliers->isEmpty()) {
                return collect();
            }

            $selected = $this->choice(
                'Which supplier would you like to check?',
                $suppliers->pluck('name', 'handle')->toArray()
            );

            return $suppliers->where('handle', $selected)->take(1);
        }

        // Find by handle or ID
        $supplier = Supplier::where('handle', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (! $supplier) {
            $this->components->error("Supplier not found: {$identifier}");

            return collect();
        }

        return collect([$supplier]);
    }
}
