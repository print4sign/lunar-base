<?php

namespace Lunar\Jobs\Suppliers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Suppliers;
use Lunar\Models\Supplier;

class SyncSupplierCatalog implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Supplier $supplier
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->supplier->isEnabled()) {
            Log::info("Supplier {$this->supplier->name} is not enabled, skipping catalog sync.");

            return;
        }

        if (! $this->supplier->supports('catalog_sync')) {
            Log::info("Supplier {$this->supplier->name} does not support catalog sync.");

            return;
        }

        Log::info("Starting catalog sync for supplier: {$this->supplier->name}");

        try {
            $driver = Suppliers::supplier($this->supplier);
            $products = $driver->syncCatalog();

            Log::info("Synced {$products->count()} products from {$this->supplier->name}");
        } catch (\Exception $e) {
            Log::error("Failed to sync catalog for {$this->supplier->name}: {$e->getMessage()}");

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['suppliers', "supplier:{$this->supplier->id}", 'catalog-sync'];
    }
}
