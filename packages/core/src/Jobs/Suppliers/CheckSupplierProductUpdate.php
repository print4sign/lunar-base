<?php

namespace Lunar\Jobs\Suppliers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Suppliers;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class CheckSupplierProductUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SupplierProduct $supplierProduct,
        public array $productData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $supplier = $this->supplierProduct->supplier;

        if (! $supplier->isEnabled()) {
            return;
        }

        $wasActive = $this->supplierProduct->active;
        $isActive = $this->productData['active'] ?? true;
        $activeTo = $this->parseActiveTo($this->productData['active_to'] ?? null);
        $replacedByExternalId = $this->productData['replaced_by_product'] ?? null;

        // Update the supplier product with lifecycle data
        $this->supplierProduct->update([
            'active' => $isActive,
            'active_to' => $activeTo,
            'replaced_by_external_id' => $replacedByExternalId,
            'external_data' => $this->productData,
            'last_synced_at' => now(),
        ]);

        // Handle discontinuation
        if ($wasActive && ! $isActive) {
            $this->handleDiscontinuation();
        }

        // Handle replacement linking
        if ($replacedByExternalId) {
            $this->handleReplacement($supplier, $replacedByExternalId);
        }

        Log::debug("Checked product update for {$this->supplierProduct->external_id}", [
            'active' => $isActive,
            'active_to' => $activeTo?->toDateString(),
            'replaced_by' => $replacedByExternalId,
        ]);
    }

    /**
     * Handle product discontinuation.
     */
    protected function handleDiscontinuation(): void
    {
        // Mark all linked variants as discontinued
        $variants = ProductVariant::where('supplier_product_id', $this->supplierProduct->id)->get();

        foreach ($variants as $variant) {
            $variant->markAsDiscontinued();

            Log::info("Marked variant {$variant->sku} as discontinued due to supplier product discontinuation", [
                'variant_id' => $variant->id,
                'supplier_product_id' => $this->supplierProduct->id,
                'external_id' => $this->supplierProduct->external_id,
            ]);
        }
    }

    /**
     * Handle product replacement.
     */
    protected function handleReplacement(Supplier $supplier, string $replacedByExternalId): void
    {
        // Find or create the replacement supplier product
        $replacement = SupplierProduct::where('supplier_id', $supplier->id)
            ->where('external_id', $replacedByExternalId)
            ->first();

        if (! $replacement) {
            // Try to fetch and create the replacement product from the API
            $driver = Suppliers::supplier($supplier);
            $replacementData = $driver->getProduct($replacedByExternalId);

            if ($replacementData) {
                $replacement = SupplierProduct::create([
                    'supplier_id' => $supplier->id,
                    'external_id' => $replacedByExternalId,
                    'external_name' => $replacementData['translations']['en']['title']
                        ?? $replacementData['translations']['nl']['title']
                        ?? $replacementData['name']
                        ?? $replacedByExternalId,
                    'external_data' => $replacementData,
                    'active' => $replacementData['active'] ?? true,
                    'active_to' => $this->parseActiveTo($replacementData['active_to'] ?? null),
                    'synced' => true,
                    'last_synced_at' => now(),
                ]);

                Log::info("Created replacement supplier product {$replacedByExternalId}", [
                    'supplier_product_id' => $replacement->id,
                ]);
            }
        }

        if ($replacement) {
            // Link to the replacement
            $this->supplierProduct->update([
                'replaced_by_id' => $replacement->id,
            ]);

            // Update linked variants to use the replacement
            $variants = ProductVariant::where('supplier_product_id', $this->supplierProduct->id)->get();

            foreach ($variants as $variant) {
                $variant->update([
                    'supplier_product_id' => $replacement->id,
                ]);

                Log::info("Migrated variant {$variant->sku} to replacement product", [
                    'variant_id' => $variant->id,
                    'old_supplier_product_id' => $this->supplierProduct->id,
                    'new_supplier_product_id' => $replacement->id,
                    'replacement_external_id' => $replacedByExternalId,
                ]);
            }
        }
    }

    /**
     * Parse the active_to date from Probo format.
     */
    protected function parseActiveTo(?string $activeTo): ?\Illuminate\Support\Carbon
    {
        if (! $activeTo) {
            return null;
        }

        try {
            $date = \Illuminate\Support\Carbon::parse($activeTo);

            // Probo uses 2999-12-30 as "never expires"
            if ($date->year >= 2999) {
                return null;
            }

            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'suppliers',
            "supplier:{$this->supplierProduct->supplier_id}",
            'product-update-check',
            "product:{$this->supplierProduct->external_id}",
        ];
    }
}
