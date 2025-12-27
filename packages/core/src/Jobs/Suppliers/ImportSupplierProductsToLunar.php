<?php

namespace Lunar\Jobs\Suppliers;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\DB;
use Lunar\Facades\Suppliers;
use Lunar\Models\Attribute;
use Lunar\Models\Currency;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Models\TaxClass;

class ImportSupplierProductsToLunar implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Supplier $supplier,
        public ?int $productTypeId = null,
        public bool $fetchPrices = true,
        public bool $skipExisting = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        if (! $this->supplier->isEnabled()) {
            Log::info("Supplier {$this->supplier->name} is not enabled, skipping import.");

            return;
        }

        $productType = $this->getProductType();
        $currency = Currency::getDefault();
        $taxClass = TaxClass::getDefault();
        $nameAttribute = $this->getNameAttributeType();

        $supplierProducts = SupplierProduct::where('supplier_id', $this->supplier->id)
            ->where('synced', true)
            ->when($this->skipExisting, function ($query) {
                $query->whereNull('product_id');
            })
            ->cursor();

        $driver = Suppliers::supplier($this->supplier);
        $imported = 0;
        $skipped = 0;

        foreach ($supplierProducts as $supplierProduct) {
            try {
                $this->importProduct(
                    $supplierProduct,
                    $productType,
                    $currency,
                    $taxClass,
                    $nameAttribute,
                    $driver
                );
                $imported++;
            } catch (\Exception $e) {
                Log::warning("Failed to import supplier product {$supplierProduct->external_id}: {$e->getMessage()}");
                $skipped++;
            }
        }

        Log::info("Imported {$imported} products from {$this->supplier->name}, skipped {$skipped}");
    }

    /**
     * Import a single supplier product as a Lunar product.
     */
    protected function importProduct(
        SupplierProduct $supplierProduct,
        ProductType $productType,
        Currency $currency,
        TaxClass $taxClass,
        string $nameAttributeType,
        $driver
    ): Product {
        return DB::transaction(function () use ($supplierProduct, $productType, $currency, $taxClass, $nameAttributeType, $driver) {
            // Build the name value depending on the field type
            $nameValue = $this->buildNameValue($supplierProduct->external_name, $nameAttributeType);

            // Create the product
            $product = Product::create([
                'status' => 'draft',
                'product_type_id' => $productType->id,
                'attribute_data' => [
                    'name' => $nameValue,
                ],
            ]);

            // Create the variant linked to the supplier product
            $variant = $product->variants()->create([
                'tax_class_id' => $taxClass->id,
                'supplier_product_id' => $supplierProduct->id,
                'sku' => $supplierProduct->external_id,
            ]);

            // Build price data
            $priceData = [
                'min_quantity' => 1,
                'currency_id' => $currency->id,
                'price' => 0,
                'supplier_id' => $this->supplier->id,
            ];

            // Fetch pricing from supplier if enabled
            if ($this->fetchPrices && $this->supplier->supports('pricing')) {
                try {
                    $priceResponse = $driver->getPrice($supplierProduct->external_id, []);
                    $priceData['price'] = (int) ($priceResponse->sellPrice * $currency->factor);
                    $priceData['cost_price'] = (int) ($priceResponse->costPrice * $currency->factor);
                } catch (\Exception $e) {
                    Log::debug("Could not fetch price for {$supplierProduct->external_id}: {$e->getMessage()}");
                }
            }

            $variant->prices()->create($priceData);

            // Update supplier product with link to Lunar product
            $supplierProduct->update([
                'product_id' => $product->id,
            ]);

            return $product;
        });
    }

    /**
     * Get the product type to use for imported products.
     */
    protected function getProductType(): ProductType
    {
        if ($this->productTypeId) {
            return ProductType::findOrFail($this->productTypeId);
        }

        // Use or create a default supplier product type
        return ProductType::firstOrCreate(
            ['name' => 'Supplier Product'],
            ['name' => 'Supplier Product']
        );
    }

    /**
     * Get the name attribute type class.
     */
    protected function getNameAttributeType(): string
    {
        $attribute = Attribute::whereHandle('name')
            ->whereAttributeType(Product::morphName())
            ->first();

        return $attribute?->type ?? \Lunar\FieldTypes\TranslatedText::class;
    }

    /**
     * Build the name value in the correct format for the field type.
     */
    protected function buildNameValue(string $name, string $nameAttributeType): mixed
    {
        // Get the default language
        $defaultLanguage = \Lunar\Models\Language::getDefault();
        $languageCode = $defaultLanguage?->code ?? 'en';

        // If it's TranslatedText, we need to pass a collection with language codes
        if ($nameAttributeType === \Lunar\FieldTypes\TranslatedText::class) {
            return new $nameAttributeType(collect([
                $languageCode => $name,
            ]));
        }

        // For simple Text type, just use the string
        return new $nameAttributeType($name);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['suppliers', "supplier:{$this->supplier->id}", 'import-products'];
    }
}
