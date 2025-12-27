<?php

namespace Lunar\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Lunar\Jobs\Suppliers\ImportSupplierProductsToLunar;
use Lunar\Jobs\Suppliers\SyncSupplierCatalog;
use Lunar\Models\ProductType;
use Lunar\Models\Supplier;

class SyncSupplierProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:supplier:sync
                            {supplier? : The supplier handle or ID to sync}
                            {--all : Sync all enabled suppliers}
                            {--import : Also import supplier products as Lunar products}
                            {--product-type= : The product type ID to use for imported products}
                            {--skip-existing : Skip products that already exist in Lunar}
                            {--no-prices : Skip fetching prices from the supplier API}
                            {--sync : Run synchronously instead of queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync product catalog from supplier(s) and optionally import as Lunar products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $suppliers = $this->getSuppliers();

        if ($suppliers->isEmpty()) {
            $this->components->error('No suppliers found to sync.');

            return self::FAILURE;
        }

        $this->components->info("Syncing {$suppliers->count()} supplier(s)...");

        $jobs = [];

        foreach ($suppliers as $supplier) {
            $this->components->twoColumnDetail($supplier->name, $supplier->driver);

            // Add catalog sync job
            $jobs[] = new SyncSupplierCatalog($supplier);

            // Add import job if requested
            if ($this->option('import')) {
                $jobs[] = new ImportSupplierProductsToLunar(
                    supplier: $supplier,
                    productTypeId: $this->option('product-type') ? (int) $this->option('product-type') : null,
                    fetchPrices: ! $this->option('no-prices'),
                    skipExisting: $this->option('skip-existing')
                );
            }
        }

        if ($this->option('sync')) {
            // Run synchronously
            $this->components->info('Running synchronously...');

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
            $this->newLine(2);
        } else {
            // Queue the jobs as a batch
            $batch = Bus::batch($jobs)
                ->name('Supplier Catalog Sync')
                ->allowFailures()
                ->dispatch();

            $this->components->info("Queued batch: {$batch->id}");
            $this->components->twoColumnDetail('Jobs queued', count($jobs));
        }

        $this->components->success('Supplier sync initiated.');

        return self::SUCCESS;
    }

    /**
     * Get the suppliers to sync.
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
                'Which supplier would you like to sync?',
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
