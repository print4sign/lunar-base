<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Facades\Suppliers;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Models\TaxClass;

class ProboProductSeeder extends Seeder
{
    /**
     * Probo products to create with sample configurations.
     */
    protected array $productsToCreate = [
        [
            'external_id' => '2024-dibond',
            'name' => ['en' => 'Dibond Sign', 'nl' => 'Dibond Bord'],
            'description' => ['en' => 'High-quality aluminum composite panel for professional signage', 'nl' => 'Hoogwaardige aluminium composiet plaat voor professionele bewegwijzering'],
            'configurations' => [
                ['width' => 500, 'height' => 500, 'quantity' => 1],
                ['width' => 1000, 'height' => 500, 'quantity' => 1],
                ['width' => 1000, 'height' => 1000, 'quantity' => 1],
            ],
        ],
        [
            'external_id' => '2024-akylite',
            'name' => ['en' => 'Akylite Plexi Sign', 'nl' => 'Akylite Plexi Bord'],
            'description' => ['en' => 'Lightweight plexi sign ideal for indoor use', 'nl' => 'Lichtgewicht plexi bord ideaal voor binnen'],
            'configurations' => [
                ['width' => 300, 'height' => 400, 'quantity' => 1],
                ['width' => 600, 'height' => 400, 'quantity' => 1],
            ],
        ],
        [
            'external_id' => '2024-backsplash',
            'name' => ['en' => 'Backsplash Panel', 'nl' => 'Achterwand Paneel'],
            'description' => ['en' => 'Custom printed backsplash panel for kitchens and bathrooms', 'nl' => 'Op maat bedrukt achterwand paneel voor keukens en badkamers'],
            'configurations' => [
                ['width' => 600, 'height' => 600, 'quantity' => 1],
                ['width' => 900, 'height' => 600, 'quantity' => 1],
            ],
        ],
        [
            'external_id' => '2024-carwrap',
            'name' => ['en' => 'Car Wrap Film', 'nl' => 'Autowrap Folie'],
            'description' => ['en' => 'Professional vehicle wrap film with custom print', 'nl' => 'Professionele voertuig wrap folie met eigen print'],
            'configurations' => [
                ['width' => 1370, 'height' => 500, 'quantity' => 1],
                ['width' => 1370, 'height' => 1000, 'quantity' => 1],
            ],
        ],
        [
            'external_id' => '2024-beach-pole',
            'name' => ['en' => 'Beach Flag', 'nl' => 'Beachvlag'],
            'description' => ['en' => 'Eye-catching beach flag for outdoor events and promotions', 'nl' => 'Opvallende beachvlag voor buitenevenementen en promoties'],
            'configurations' => [
                ['quantity' => 1],
                ['quantity' => 5],
                ['quantity' => 10],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating Probo configurable products...');

        $supplier = Supplier::where('handle', 'probo')->first();

        if (! $supplier) {
            $this->command->error('Probo supplier not found. Please run DemoDataSeeder first.');
            return;
        }

        // Get or create the configurable product type
        $productType = ProductType::where('name', 'Supplier Product')->first();

        if (! $productType) {
            $productType = ProductType::first();
        }

        // Mark it as configurable
        $productType->update([
            'configurable' => true,
        ]);

        $currency = Currency::getDefault();
        $taxClass = TaxClass::getDefault();
        $channel = Channel::getDefault();
        $customerGroup = CustomerGroup::getDefault();

        $createdCount = 0;

        foreach ($this->productsToCreate as $productData) {
            $supplierProduct = SupplierProduct::where('external_id', $productData['external_id'])
                ->where('supplier_id', $supplier->id)
                ->first();

            if (! $supplierProduct) {
                $this->command->warn("Supplier product {$productData['external_id']} not found, skipping...");
                continue;
            }

            // Check if product already exists
            $existingProduct = Product::whereHas('variants', function ($query) use ($supplierProduct) {
                $query->where('supplier_product_id', $supplierProduct->id);
            })->first();

            if ($existingProduct) {
                $this->command->info("Product for {$productData['external_id']} already exists, skipping...");
                continue;
            }

            $this->command->info("Creating product: {$productData['name']['en']}");

            // Create the product
            $product = Product::create([
                'product_type_id' => $productType->id,
                'status' => 'published',
                'brand_id' => null,
                'attribute_data' => [
                    'name' => new TranslatedText($productData['name']),
                    'description' => new TranslatedText($productData['description']),
                ],
            ]);

            // Attach to channel and customer group
            $product->channels()->attach($channel->id, [
                'starts_at' => now(),
                'enabled' => true,
            ]);

            $product->customerGroups()->attach($customerGroup->id, [
                'purchasable' => true,
                'visible' => true,
                'enabled' => true,
                'starts_at' => now(),
            ]);

            // Create variants for each configuration
            foreach ($productData['configurations'] as $index => $config) {
                $config['quantity'] = $config['quantity'] ?? 1;

                // Generate configuration hash
                $configHash = $this->generateConfigurationHash($supplierProduct->id, $config);
                $config['hash'] = $configHash;

                // Generate SKU
                $skuParts = [$supplierProduct->external_id];
                if (isset($config['width'])) {
                    $skuParts[] = $config['width'] . 'x' . $config['height'];
                }
                $skuParts[] = 'Q' . $config['quantity'];
                $sku = implode('-', $skuParts);

                // Try to get price from Probo
                $costPrice = 0;
                $sellPrice = 0;

                try {
                    $priceResponse = Suppliers::supplier($supplier)
                        ->getPrice($supplierProduct->external_id, $config);

                    $costPrice = $priceResponse->costPrice;
                    $sellPrice = $priceResponse->sellPrice;

                    $this->command->info("  - Variant {$sku}: Cost {$costPrice}, Sell {$sellPrice} cents");
                } catch (\Exception $e) {
                    $this->command->warn("  - Could not get price for {$sku}: {$e->getMessage()}");
                    // Use placeholder pricing
                    $costPrice = 1000 + ($index * 500);
                    $sellPrice = $costPrice * 2;
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

                // Link supplier product to first variant
                if ($index === 0) {
                    $supplierProduct->update(['product_variant_id' => $variant->id]);
                }
            }

            $createdCount++;
        }

        $this->command->info("Created {$createdCount} products with configured variants.");
        $this->command->newLine();
        $this->command->info('You can now:');
        $this->command->info('1. View products in the admin panel');
        $this->command->info('2. Use the "Configure Probo Product" action to add more variants');
        $this->command->info('3. Add products to cart - prices will be fetched from Probo in real-time');
    }

    /**
     * Generate a configuration hash.
     */
    protected function generateConfigurationHash(int $supplierProductId, array $config): string
    {
        unset($config['hash']);
        ksort($config);

        return md5(json_encode([
            'supplier_product_id' => $supplierProductId,
            'configuration' => $config,
        ]));
    }
}
