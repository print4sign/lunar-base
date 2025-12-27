<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Facades\Suppliers;
use Lunar\Models\Currency;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Models\TaxClass;

class AddProboConfiguredVariantsSeeder extends Seeder
{
    /**
     * Products with configurations to add.
     */
    protected array $configurationsToAdd = [
        '2024-dibond' => [
            ['width' => 500, 'height' => 500],
            ['width' => 1000, 'height' => 500],
            ['width' => 1000, 'height' => 1000],
            ['width' => 2000, 'height' => 1000],
        ],
        '2024-akylite' => [
            ['width' => 300, 'height' => 400],
            ['width' => 600, 'height' => 400],
            ['width' => 600, 'height' => 800],
        ],
        '2024-backsplash' => [
            ['width' => 600, 'height' => 600],
            ['width' => 900, 'height' => 600],
            ['width' => 1200, 'height' => 600],
        ],
        '2024-carwrap' => [
            ['width' => 1370, 'height' => 500],
            ['width' => 1370, 'height' => 1000],
            ['width' => 1370, 'height' => 2000],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Adding configured variants to existing Probo products...');

        $supplier = Supplier::where('handle', 'probo')->first();

        if (! $supplier) {
            $this->command->error('Probo supplier not found.');
            return;
        }

        // Make sure ProductType is configurable
        $productType = ProductType::where('name', 'Supplier Product')->first();
        if ($productType) {
            $productType->update(['configurable' => true]);
        }

        $currency = Currency::getDefault();
        $taxClass = TaxClass::getDefault();

        $variantsCreated = 0;

        foreach ($this->configurationsToAdd as $externalId => $configurations) {
            $supplierProduct = SupplierProduct::where('external_id', $externalId)
                ->where('supplier_id', $supplier->id)
                ->first();

            if (! $supplierProduct) {
                $this->command->warn("Supplier product {$externalId} not found, skipping...");
                continue;
            }

            // Find the product that has a variant linked to this supplier product
            $existingVariant = ProductVariant::where('supplier_product_id', $supplierProduct->id)->first();

            if (! $existingVariant) {
                $this->command->warn("No variant found for {$externalId}, skipping...");
                continue;
            }

            $product = $existingVariant->product;
            $this->command->info("Adding variants to: {$product->attr('name')}");

            foreach ($configurations as $config) {
                $config['quantity'] = 1;

                // Generate SKU
                $sku = $externalId . '-' . $config['width'] . 'x' . $config['height'];

                // Check if variant already exists
                $existingConfiguredVariant = ProductVariant::where('sku', $sku)->first();
                if ($existingConfiguredVariant) {
                    $this->command->info("  - Variant {$sku} already exists, skipping...");
                    continue;
                }

                // Generate hash
                $config['hash'] = $this->generateConfigurationHash($supplierProduct->id, $config);

                // Try to get price from Probo
                $costPrice = 0;
                $sellPrice = 0;

                try {
                    $priceResponse = Suppliers::supplier($supplier)
                        ->getPrice($supplierProduct->external_id, $config);

                    $costPrice = $priceResponse->costPrice;
                    $sellPrice = $priceResponse->sellPrice;

                    $this->command->info("  - Creating {$sku}: Cost €" . number_format($costPrice / 100, 2) . ", Sell €" . number_format($sellPrice / 100, 2));
                } catch (\Exception $e) {
                    $this->command->warn("  - Could not get price for {$sku}: " . substr($e->getMessage(), 0, 100));
                    // Calculate placeholder pricing based on dimensions
                    $area = ($config['width'] * $config['height']) / 1000000; // m²
                    $costPrice = (int) ($area * 5000); // €50/m² base cost
                    $sellPrice = (int) ($costPrice * 2.5); // 2.5x markup
                    $this->command->info("  - Using placeholder: Cost €" . number_format($costPrice / 100, 2) . ", Sell €" . number_format($sellPrice / 100, 2));
                }

                // Create the variant
                $variant = $product->variants()->create([
                    'sku' => $sku,
                    'tax_class_id' => $taxClass->id,
                    'supplier_product_id' => $supplierProduct->id,
                    'configuration' => $config,
                    'stock' => 0,
                    'purchasable' => 'always',
                ]);

                // Create price
                $variant->prices()->create([
                    'currency_id' => $currency->id,
                    'price' => $sellPrice,
                    'cost_price' => $costPrice,
                    'min_quantity' => 1,
                    'supplier_id' => $supplier->id,
                ]);

                $variantsCreated++;
            }
        }

        $this->command->newLine();
        $this->command->info("Created {$variantsCreated} configured variants.");
        $this->command->newLine();
        $this->command->info('Products are now available with size configurations!');
        $this->command->info('Visit the admin panel to see the products and their variants.');
    }

    /**
     * Generate a configuration hash.
     */
    protected function generateConfigurationHash(int $supplierProductId, array $config): string
    {
        $configForHash = $config;
        unset($configForHash['hash']);
        ksort($configForHash);

        return md5(json_encode([
            'supplier_product_id' => $supplierProductId,
            'configuration' => $configForHash,
        ]));
    }
}
