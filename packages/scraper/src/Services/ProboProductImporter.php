<?php

namespace Lunar\Scraper\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lunar\FieldTypes\Dropdown;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\Toggle;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Scraper\FieldTypes\TranslatedCodeFieldValue;
use Lunar\Scraper\FieldTypes\TranslatedRepeaterFieldValue;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Models\TaxClass;
use Lunar\Models\Url;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class ProboProductImporter
{
    protected ?ProductType $productType = null;

    protected ?Currency $currency = null;

    protected ?TaxClass $taxClass = null;

    protected ?string $defaultLanguage = null;

    protected ?AttributeGroup $detailsGroup = null;

    protected ?AttributeGroup $deliveryGroup = null;

    protected ?AttributeGroup $seoGroup = null;

    protected ?AttributeGroup $featuresGroup = null;

    protected ?AttributeGroup $resourcesGroup = null;

    protected ?AttributeGroup $proboGroup = null;

    protected ?ClaudeClient $claudeClient = null;

    protected ?ImageDownloader $imageDownloader = null;

    protected bool $downloadImages = false;

    protected bool $updateImages = false;

    /**
     * Cache for batch translations during a single product import.
     * Structure: ['en' => ['key' => 'translated'], 'de' => [...], ...]
     */
    protected array $translationCache = [];

    public function __construct(?ClaudeClient $claudeClient = null, bool $downloadImages = false)
    {
        $this->claudeClient = $claudeClient;
        $this->downloadImages = $downloadImages;

        if ($downloadImages) {
            $this->imageDownloader = new ImageDownloader();
        }
        $this->productType = $this->getProductType();
        $this->currency = Currency::getDefault();
        $this->taxClass = TaxClass::getDefault();
        $this->defaultLanguage = Language::getDefault()?->code ?? 'en';

        // Initialize attribute groups
        $this->detailsGroup = $this->getDetailsAttributeGroup();
        $this->deliveryGroup = $this->getDeliveryAttributeGroup();
        $this->seoGroup = $this->getSeoAttributeGroup();
        $this->featuresGroup = $this->getFeaturesAttributeGroup();
        $this->resourcesGroup = $this->getResourcesAttributeGroup();
        $this->proboGroup = $this->getProboAttributeGroup();

        // Ensure all attributes exist
        $this->ensureDetailsAttributesExist();
        $this->ensureDeliveryAttributesExist();
        $this->ensureSeoAttributesExist();
        $this->ensureFeaturesAttributesExist();
        $this->ensureResourcesAttributesExist();
        $this->ensureProboAttributesExist();
    }

    /**
     * Set whether to update existing images.
     */
    public function setUpdateImages(bool $update): self
    {
        $this->updateImages = $update;

        return $this;
    }

    /**
     * Clean up old attribute groups and attributes.
     * This removes all product attributes and groups created by this importer.
     */
    public static function cleanupAttributes(): void
    {
        $groupHandles = ['details', 'delivery', 'seo', 'features', 'resources', 'specifications', 'probo'];

        // Get all attribute groups to clean up
        $groups = AttributeGroup::where('attributable_type', 'product')
            ->whereIn('handle', $groupHandles)
            ->get();

        foreach ($groups as $group) {
            // Get all attributes in this group
            $attributes = Attribute::where('attribute_group_id', $group->id)->get();

            foreach ($attributes as $attribute) {
                // Detach from all product types
                DB::table('lunar_attributables')
                    ->where('attribute_id', $attribute->id)
                    ->delete();

                // Delete the attribute
                $attribute->delete();

                Log::info("Deleted attribute: {$attribute->handle}");
            }

            // Delete the group
            $group->delete();

            Log::info("Deleted attribute group: {$group->handle}");
        }

        // Also clean up any orphaned attributes with our known handles
        $attributeHandles = [
            'name', 'description', 'short-description',
            'delivery-title', 'delivery-subtitle',
            'meta-title', 'meta-description', 'meta-keywords',
            'product-benefits', 'downloads', 'faq',
        ];

        $orphanedAttributes = Attribute::where('attribute_type', 'product')
            ->whereIn('handle', $attributeHandles)
            ->get();

        foreach ($orphanedAttributes as $attribute) {
            DB::table('lunar_attributables')
                ->where('attribute_id', $attribute->id)
                ->delete();

            $attribute->delete();

            Log::info("Deleted orphaned attribute: {$attribute->handle}");
        }

        Log::info('Attribute cleanup completed');
    }

    /**
     * Import a scraped product and create Lunar models.
     */
    public function import(array $scrapedProduct, Supplier $supplier): SupplierProduct
    {
        $productName = $scrapedProduct['name'] ?? 'Unknown';
        Log::info("=== Starting import for: {$productName} ===");
        Log::debug('Scraped product data keys: '.implode(', ', array_keys($scrapedProduct)));

        return DB::transaction(function () use ($scrapedProduct, $supplier, $productName) {
            // 1. Create or update SupplierProduct (only updates scraped_at and html_content if exists)
            Log::info("Step 1: Creating/updating SupplierProduct for: {$productName}");
            $supplierProduct = $this->createOrUpdateSupplierProduct($scrapedProduct, $supplier);
            Log::info("SupplierProduct ID: {$supplierProduct->id}, external_id: {$supplierProduct->external_id}");

            // 2. Get or create the linked Lunar Product
            $product = $supplierProduct->product_id ? Product::find($supplierProduct->product_id) : null;

            Log::info("Step 2: Product lookup - existing Product ID: ".($product?->id ?? 'null'));

            if ($product) {
                Log::info("Updating existing Product ID: {$product->id}");
                $this->updateProductAttributes($product, $scrapedProduct);
            } else {
                Log::info("Creating new Product for: {$productName}");
                $product = $this->createProduct($scrapedProduct, $supplierProduct);
                $supplierProduct->update(['product_id' => $product->id]);
                Log::info("Created new Product ID: {$product->id}");
            }

            // 3. Download and attach images if enabled
            if ($this->downloadImages && ! empty($scrapedProduct['images'])) {
                $imageCount = count($scrapedProduct['images']);
                Log::info("Step 3: Downloading {$imageCount} images for Product ID: {$product->id}");
                $this->downloadAndAttachImages($product, $scrapedProduct['images']);
            } else {
                Log::debug('Step 3: Skipping image download (disabled or no images)');
            }

            Log::info("=== Import complete for: {$productName} ===");

            return $supplierProduct->fresh();
        });
    }

    /**
     * Update existing Product attributes with scraped data.
     */
    protected function updateProductAttributes(Product $product, array $scrapedProduct): void
    {
        Log::debug("updateProductAttributes() for Product ID: {$product->id}");

        // Clear translation cache and batch translate for this product
        $this->clearTranslationCache();
        $translations = $scrapedProduct['translations'] ?? [];
        $textsToTranslate = $this->collectTextsForTranslation($scrapedProduct, $translations);
        if (! empty($textsToTranslate)) {
            $this->batchTranslateForProduct($textsToTranslate);
        }

        $existingData = $product->attribute_data;
        $attributeData = $existingData instanceof \Illuminate\Support\Collection ? $existingData->toArray() : ($existingData ?? []);
        Log::debug('Existing attribute_data keys: '.implode(', ', array_keys($attributeData)));

        // Get translations data
        $enData = $translations['en'] ?? [];
        $nlData = $translations['nl'] ?? [];
        $deData = $translations['de'] ?? [];
        $frData = $translations['fr'] ?? [];
        $esData = $translations['es'] ?? [];

        Log::debug('Translation languages available: '.implode(', ', array_keys($translations)));

        // Update name with translations
        $nlName = $nlData['title'] ?? $scrapedProduct['name'] ?? null;
        Log::debug("Name source: ".($nlData['title'] ?? null ? 'translations.nl.title' : 'scrapedProduct.name')." = {$nlName}");
        if ($nlName) {
            $enName = $enData['title'] ?? $this->getCachedTranslation($nlName, 'en');
            $deName = $deData['title'] ?? $this->getCachedTranslation($nlName, 'de');
            $frName = $frData['title'] ?? $this->getCachedTranslation($nlName, 'fr');
            $esName = $esData['title'] ?? $this->getCachedTranslation($nlName, 'es');
            $attributeData['name'] = new TranslatedText(collect([
                'en' => $enName,
                'nl' => $nlName,
                'de' => $deName,
                'fr' => $frName,
                'es' => $esName,
            ]));
        }

        // Update description with translations (keep HTML)
        // Prefer the full scraped description over short API translations
        // The API translation description is often just the product name/title, not a real description
        $scrapedDesc = $scrapedProduct['description'] ?? '';
        $translationDesc = $nlData['description'] ?? '';

        // Use scraped description if it's substantively longer (more than 50 chars longer)
        // This ensures we get the full HTML description, not just a title from translations
        if (strlen($scrapedDesc) > strlen($translationDesc) + 50) {
            $descSource = $scrapedDesc;
            Log::debug("Description source: scrapedProduct.description (longer than translation by ".
                (strlen($scrapedDesc) - strlen($translationDesc))." chars)");
        } else {
            $descSource = $translationDesc ?: $scrapedDesc;
            Log::debug("Description source: ".($translationDesc ? 'translations.nl.description' : 'scrapedProduct.description'));
        }

        $nlDesc = $this->transformContent($descSource);
        Log::debug("Description length: ".strlen($nlDesc)." chars");

        if ($nlDesc) {
            $enDesc = $this->transformContent($enData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'en');
            $deDesc = $this->transformContent($deData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'de');
            $frDesc = $this->transformContent($frData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'fr');
            $esDesc = $this->transformContent($esData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'es');
            $attributeData['description'] = new TranslatedText(collect([
                'en' => $enDesc,
                'nl' => $nlDesc,
                'de' => $deDesc,
                'fr' => $frDesc,
                'es' => $esDesc,
            ]));
            Log::info("Set description for all 5 locales (nl length: ".strlen($nlDesc).")");
        } else {
            Log::warning("No description found in scraped data");
        }

        // Update short description with translations
        $nlShort = $nlData['short_description'] ?? $scrapedProduct['short_description'] ?? '';
        Log::debug("Short description length: ".strlen($nlShort)." chars");
        if ($nlShort) {
            $enShort = $enData['short_description'] ?? $this->getCachedTranslation($nlShort, 'en');
            $deShort = $deData['short_description'] ?? $this->getCachedTranslation($nlShort, 'de');
            $frShort = $frData['short_description'] ?? $this->getCachedTranslation($nlShort, 'fr');
            $esShort = $esData['short_description'] ?? $this->getCachedTranslation($nlShort, 'es');
            $attributeData['short-description'] = new TranslatedText(collect([
                'en' => $enShort,
                'nl' => $nlShort,
                'de' => $deShort,
                'fr' => $frShort,
                'es' => $esShort,
            ]));
        }

        // Update product benefits as translatable repeater
        $productBenefits = $scrapedProduct['product_benefits'] ?? [];
        if (! empty($productBenefits)) {
            // Ensure the product-benefits attribute exists (it's in the Features group)
            $this->ensureFeaturesAttributesExist();

            $keyedBenefits = [];
            foreach ($productBenefits as $benefit) {
                $keyedBenefits[(string) Str::uuid()] = ['value' => $benefit];
            }
            $attributeData['product-benefits'] = new TranslatedRepeaterFieldValue([
                'nl' => $keyedBenefits,
                'en' => $keyedBenefits,
            ]);
        }

        // Update product pros and cons (Plus- en minpunten)
        $prosAndCons = $scrapedProduct['pros_and_cons'] ?? [];
        if (! empty($prosAndCons['pros'])) {
            $this->ensureFeaturesAttributesExist();
            $keyedPros = [];
            foreach ($prosAndCons['pros'] as $pro) {
                $keyedPros[(string) Str::uuid()] = ['value' => $pro];
            }
            $attributeData['product-pros'] = new TranslatedRepeaterFieldValue([
                'nl' => $keyedPros,
                'en' => $keyedPros,
            ]);
        }
        if (! empty($prosAndCons['cons'])) {
            $this->ensureFeaturesAttributesExist();
            $keyedCons = [];
            foreach ($prosAndCons['cons'] as $con) {
                $keyedCons[(string) Str::uuid()] = ['value' => $con];
            }
            $attributeData['product-cons'] = new TranslatedRepeaterFieldValue([
                'nl' => $keyedCons,
                'en' => $keyedCons,
            ]);
        }

        // Store specificaties HTML to input folder and save slug as attribute
        $specificatiesHtml = $scrapedProduct['specificaties_html'] ?? null;
        if (! empty($specificatiesHtml)) {
            $specSlug = $this->saveSpecificationsHtml($product, $specificatiesHtml);
            if ($specSlug) {
                $attributeData['specificaties'] = new TranslatedText(collect([
                    'nl' => $specSlug,
                    'en' => $specSlug,
                ]));
            }
        }

        // Update Probo-specific attributes
        $this->updateProboAttributes($attributeData, $scrapedProduct);

        Log::debug('Final attribute_data keys to save: '.implode(', ', array_keys($attributeData)));

        $product->update(['attribute_data' => $attributeData]);

        Log::info("Updated Lunar Product attributes for Product ID: {$product->id}", [
            'name' => $nlName ?? '(not set)',
            'name_length' => strlen($nlName ?? ''),
            'description_length' => strlen($nlDesc ?? ''),
            'short_description_length' => strlen($nlShort ?? ''),
            'benefits_count' => count($productBenefits),
            'pros_count' => count($prosAndCons['pros'] ?? []),
            'cons_count' => count($prosAndCons['cons'] ?? []),
            'has_specificaties' => ! empty($specificatiesHtml),
        ]);
    }

    protected function createOrUpdateSupplierProduct(array $scrapedProduct, Supplier $supplier): SupplierProduct
    {
        // Generate stable external_id from URL (most reliable identifier)
        $externalId = $this->generateExternalId($scrapedProduct);

        if (! $externalId) {
            throw new \InvalidArgumentException('Scraped product must have url, product_id, sku, or external_id');
        }

        // Check if SupplierProduct already exists
        $existingSupplierProduct = SupplierProduct::where('supplier_id', $supplier->id)
            ->where('external_id', $externalId)
            ->first();

        if ($existingSupplierProduct) {
            // Update last_synced_at and refresh external_data with latest scraped data
            $existingSupplierProduct->update([
                'last_synced_at' => now(),
                'external_data' => array_merge($existingSupplierProduct->external_data ?? [], [
                    'api_code' => $scrapedProduct['api_code'] ?? $scrapedProduct['meta']['api_code'] ?? null,
                    'url' => $scrapedProduct['url'] ?? null,
                    'images' => $scrapedProduct['images'] ?? [],
                ]),
            ]);

            return $existingSupplierProduct;
        }

        // Create new SupplierProduct with minimal data (just for API relation tracking)
        return SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'external_id' => $externalId,
            'external_name' => $scrapedProduct['name'] ?? 'Unknown Product',
            'external_data' => [
                'api_code' => $scrapedProduct['api_code'] ?? $scrapedProduct['meta']['api_code'] ?? null,
                'url' => $scrapedProduct['url'] ?? null,
                'images' => $scrapedProduct['images'] ?? [],
            ],
            'active' => true,
            'synced' => true,
            'last_synced_at' => now(),
        ]);
    }

    protected function createProduct(array $scrapedProduct, SupplierProduct $supplierProduct): Product
    {
        // Build attribute data with translations and scraped attributes
        $attributeData = $this->buildAttributeData($scrapedProduct);

        // Create the product
        $product = Product::create([
            'status' => 'draft',
            'product_type_id' => $this->productType->id,
            'attribute_data' => $attributeData,
        ]);

        // Create URLs for all languages with translated slugs
        $this->createMultilingualUrls($product, $attributeData, $scrapedProduct['name'] ?? 'product');

        // Get unit code from pricelist_table or tier_pricing
        $unitCode = $scrapedProduct['pricelist_table']['unit']
            ?? $scrapedProduct['tier_pricing_above']['unit']
            ?? $scrapedProduct['tier_pricing']['unit']
            ?? null;

        // Normalize unit code to match UnitCode enum values
        $unitCode = $this->normalizeUnitCode($unitCode);

        // Create the variant (dynamic for supplier-backed products with configurator)
        $variant = $product->variants()->create([
            'tax_class_id' => $this->taxClass->id,
            'supplier_product_id' => $supplierProduct->id,
            'sku' => $scrapedProduct['sku'] ?? $supplierProduct->external_id,
            'is_dynamic' => true,
            'unit_code' => $unitCode,
        ]);

        // Use tier pricing if available (check both tier_pricing and tier_pricing_above)
        $tierPricing = $scrapedProduct['tier_pricing'] ?? $scrapedProduct['tier_pricing_above'] ?? null;

        if (! empty($tierPricing['tiers'])) {
            // Create tier prices for each quantity tier
            foreach ($tierPricing['tiers'] as $tier) {
                $minQuantity = (int) ceil((float) ($tier['quantity'] ?? 1));
                $priceValue = (int) round((float) $tier['price'] * $this->currency->factor);

                $variant->prices()->create([
                    'min_quantity' => max(1, $minQuantity),
                    'currency_id' => $this->currency->id,
                    'price' => $priceValue,
                    'supplier_id' => $supplierProduct->supplier_id,
                ]);
            }

            Log::info("Created tier prices for variant", [
                'variant_id' => $variant->id,
                'tiers_count' => count($tierPricing['tiers']),
                'unit' => $tierPricing['unit'] ?? null,
            ]);
        } else {
            // Fall back to single price from scraped data
            $priceValue = $this->parsePrice($scrapedProduct['price'] ?? null);

            $variant->prices()->create([
                'min_quantity' => 1,
                'currency_id' => $this->currency->id,
                'price' => $priceValue,
                'supplier_id' => $supplierProduct->supplier_id,
            ]);
        }

        // Save specificaties HTML to input folder and update product attribute
        $specificatiesHtml = $scrapedProduct['specificaties_html'] ?? null;
        if (! empty($specificatiesHtml)) {
            $specSlug = $this->saveSpecificationsHtml($product, $specificatiesHtml);
            if ($specSlug) {
                $attributeData['specificaties'] = new TranslatedText(collect([
                    'nl' => $specSlug,
                    'en' => $specSlug,
                ]));
                $product->update(['attribute_data' => $attributeData]);
            }
        }

        Log::info("Created Lunar Product for: {$attributeData['name']->getValue()}", [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'sku' => $variant->sku,
            'urls_created' => $product->urls()->count(),
            'price' => $priceValue,
            'attributes_count' => count($attributeData),
        ]);

        return $product;
    }

    /**
     * Build attribute data from scraped product with translations.
     */
    protected function buildAttributeData(array $scrapedProduct): array
    {
        // Clear translation cache for this product
        $this->clearTranslationCache();

        $translations = $scrapedProduct['translations'] ?? [];
        $enData = $translations['en'] ?? [];
        $nlData = $translations['nl'] ?? [];
        $deData = $translations['de'] ?? [];
        $frData = $translations['fr'] ?? [];
        $esData = $translations['es'] ?? [];

        // Collect all Dutch texts that need translation (where no translation exists in scraped data)
        $textsToTranslate = $this->collectTextsForTranslation($scrapedProduct, $translations);

        // Batch translate all texts at once (4 API calls total - one per target language)
        if (! empty($textsToTranslate)) {
            $this->batchTranslateForProduct($textsToTranslate);
        }

        $data = [];

        // Name with translations (use 'title' from translations, fall back to 'name' from scraped data)
        // The translations block uses 'title' field, not 'name'
        $nlName = $nlData['title'] ?? $scrapedProduct['name'] ?? 'Unknown';
        $enName = $enData['title'] ?? $this->getCachedTranslation($nlName, 'en');
        $deName = $deData['title'] ?? $this->getCachedTranslation($nlName, 'de');
        $frName = $frData['title'] ?? $this->getCachedTranslation($nlName, 'fr');
        $esName = $esData['title'] ?? $this->getCachedTranslation($nlName, 'es');
        $data['name'] = new TranslatedText(collect([
            'en' => $enName,
            'nl' => $nlName,
            'de' => $deName,
            'fr' => $frName,
            'es' => $esName,
        ]));

        // Description with translations (keep HTML, transform Probo content)
        // Prefer the full scraped description over short API translations
        // The API translation description is often just the product name/title, not a real description
        $scrapedDesc = $scrapedProduct['description'] ?? '';
        $translationDesc = $nlData['description'] ?? '';

        // Use scraped description if it's substantively longer (more than 50 chars longer)
        $descSource = (strlen($scrapedDesc) > strlen($translationDesc) + 50)
            ? $scrapedDesc
            : ($translationDesc ?: $scrapedDesc);

        $nlDesc = $this->transformContent($descSource);
        $enDesc = $this->transformContent($enData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'en');
        $deDesc = $this->transformContent($deData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'de');
        $frDesc = $this->transformContent($frData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'fr');
        $esDesc = $this->transformContent($esData['description'] ?? '') ?: $this->getCachedTranslation($nlDesc, 'es');
        if ($enDesc || $nlDesc) {
            $data['description'] = new TranslatedText(collect([
                'en' => $enDesc,
                'nl' => $nlDesc,
                'de' => $deDesc,
                'fr' => $frDesc,
                'es' => $esDesc,
            ]));
        }

        // Short description with translations
        $nlShort = $nlData['short_description'] ?? $scrapedProduct['short_description'] ?? '';
        $enShort = $enData['short_description'] ?? $this->getCachedTranslation($nlShort, 'en');
        $deShort = $deData['short_description'] ?? $this->getCachedTranslation($nlShort, 'de');
        $frShort = $frData['short_description'] ?? $this->getCachedTranslation($nlShort, 'fr');
        $esShort = $esData['short_description'] ?? $this->getCachedTranslation($nlShort, 'es');
        if ($enShort || $nlShort) {
            $data['short-description'] = new TranslatedText(collect([
                'en' => $enShort,
                'nl' => $nlShort,
                'de' => $deShort,
                'fr' => $frShort,
                'es' => $esShort,
            ]));
        }

        // Delivery attributes
        $deliveryInfo = $scrapedProduct['delivery_info'] ?? [];
        if (! empty($deliveryInfo['title'])) {
            $nlDeliveryTitle = $deliveryInfo['title'];
            $data['delivery-title'] = new TranslatedText(collect([
                'en' => $this->getCachedTranslation($nlDeliveryTitle, 'en'),
                'nl' => $nlDeliveryTitle,
            ]));
        }
        if (! empty($deliveryInfo['subtitle'])) {
            $nlDeliverySubtitle = $deliveryInfo['subtitle'];
            $data['delivery-subtitle'] = new TranslatedText(collect([
                'en' => $this->getCachedTranslation($nlDeliverySubtitle, 'en'),
                'nl' => $nlDeliverySubtitle,
            ]));
        }

        // SEO attributes (transform Probo references, translate to English)
        $seo = $scrapedProduct['seo'] ?? [];
        if (! empty($seo['title'])) {
            $nlSeoTitle = $this->transformContent($seo['title']);
            $data['meta-title'] = new TranslatedText(collect([
                'en' => $this->getCachedTranslation($nlSeoTitle, 'en'),
                'nl' => $nlSeoTitle,
            ]));
        }
        if (! empty($seo['description'])) {
            $nlSeoDesc = $this->transformContent($seo['description']);
            $data['meta-description'] = new TranslatedText(collect([
                'en' => $this->getCachedTranslation($nlSeoDesc, 'en'),
                'nl' => $nlSeoDesc,
            ]));
        }
        if (! empty($seo['keywords'])) {
            $data['meta-keywords'] = new Text($seo['keywords']);
        }

        // Product benefits as translatable repeater
        $productBenefits = $scrapedProduct['product_benefits'] ?? [];
        if (! empty($productBenefits)) {
            $keyedBenefits = [];
            foreach ($productBenefits as $benefit) {
                $keyedBenefits[(string) Str::uuid()] = ['value' => $benefit];
            }
            $data['product-benefits'] = new TranslatedRepeaterFieldValue([
                'nl' => $keyedBenefits,
                'en' => $keyedBenefits,
            ]);
        }

        // Product pros and cons (Plus- en minpunten)
        $prosAndCons = $scrapedProduct['pros_and_cons'] ?? [];
        if (! empty($prosAndCons['pros'])) {
            $keyedPros = [];
            foreach ($prosAndCons['pros'] as $pro) {
                $keyedPros[(string) Str::uuid()] = ['value' => $pro];
            }
            $data['product-pros'] = new TranslatedRepeaterFieldValue([
                'nl' => $keyedPros,
                'en' => $keyedPros,
            ]);
        }
        if (! empty($prosAndCons['cons'])) {
            $keyedCons = [];
            foreach ($prosAndCons['cons'] as $con) {
                $keyedCons[(string) Str::uuid()] = ['value' => $con];
            }
            $data['product-cons'] = new TranslatedRepeaterFieldValue([
                'nl' => $keyedCons,
                'en' => $keyedCons,
            ]);
        }

        // Downloads - use translatable field with Repeater admin display per language
        $downloads = $scrapedProduct['downloads'] ?? [];
        if (! empty($downloads)) {
            $nlDownloads = [];
            $enDownloads = [];
            $deDownloads = [];
            $frDownloads = [];
            $esDownloads = [];

            foreach ($downloads as $item) {
                $key = (string) Str::uuid();
                $nlTitle = $item['title'] ?? '';
                $nlDownloads[$key] = $item;
                $enDownloads[$key] = ['title' => $this->getCachedTranslation($nlTitle, 'en'), 'url' => $item['url'] ?? ''];
                $deDownloads[$key] = ['title' => $this->getCachedTranslation($nlTitle, 'de'), 'url' => $item['url'] ?? ''];
                $frDownloads[$key] = ['title' => $this->getCachedTranslation($nlTitle, 'fr'), 'url' => $item['url'] ?? ''];
                $esDownloads[$key] = ['title' => $this->getCachedTranslation($nlTitle, 'es'), 'url' => $item['url'] ?? ''];
            }

            $data['downloads'] = new TranslatedRepeaterFieldValue([
                'nl' => $nlDownloads,
                'en' => $enDownloads,
                'de' => $deDownloads,
                'fr' => $frDownloads,
                'es' => $esDownloads,
            ]);
        }

        // FAQ - use translatable field with Repeater admin display per language
        $faq = $scrapedProduct['faq'] ?? [];
        if (! empty($faq)) {
            $nlFaq = [];
            $enFaq = [];
            $deFaq = [];
            $frFaq = [];
            $esFaq = [];

            foreach ($faq as $item) {
                $key = (string) Str::uuid();
                $nlQuestion = $item['question'] ?? '';
                $nlAnswer = $item['answer'] ?? '';

                $nlFaq[$key] = $item;
                $enFaq[$key] = [
                    'question' => $this->getCachedTranslation($nlQuestion, 'en'),
                    'answer' => $this->getCachedTranslation($nlAnswer, 'en'),
                ];
                $deFaq[$key] = [
                    'question' => $this->getCachedTranslation($nlQuestion, 'de'),
                    'answer' => $this->getCachedTranslation($nlAnswer, 'de'),
                ];
                $frFaq[$key] = [
                    'question' => $this->getCachedTranslation($nlQuestion, 'fr'),
                    'answer' => $this->getCachedTranslation($nlAnswer, 'fr'),
                ];
                $esFaq[$key] = [
                    'question' => $this->getCachedTranslation($nlQuestion, 'es'),
                    'answer' => $this->getCachedTranslation($nlAnswer, 'es'),
                ];
            }

            $data['faq'] = new TranslatedRepeaterFieldValue([
                'nl' => $nlFaq,
                'en' => $enFaq,
                'de' => $deFaq,
                'fr' => $frFaq,
                'es' => $esFaq,
            ]);
        }

        // Specificaties HTML will be saved as Blade file after product is created
        // See createProduct() which calls saveSpecificationsAsBladeFile()

        // Option alerts - warning/info messages about product options
        $optionAlerts = $scrapedProduct['option_alerts'] ?? [];
        if (! empty($optionAlerts)) {
            $nlAlerts = [];
            $enAlerts = [];
            $deAlerts = [];
            $frAlerts = [];
            $esAlerts = [];

            foreach ($optionAlerts as $alert) {
                $key = (string) Str::uuid();
                $nlAlerts[$key] = ['message' => $alert];
                $enAlerts[$key] = ['message' => $this->getCachedTranslation($alert, 'en')];
                $deAlerts[$key] = ['message' => $this->getCachedTranslation($alert, 'de')];
                $frAlerts[$key] = ['message' => $this->getCachedTranslation($alert, 'fr')];
                $esAlerts[$key] = ['message' => $this->getCachedTranslation($alert, 'es')];
            }

            $data['option-alerts'] = new TranslatedRepeaterFieldValue([
                'nl' => $nlAlerts,
                'en' => $enAlerts,
                'de' => $deAlerts,
                'fr' => $frAlerts,
                'es' => $esAlerts,
            ]);
        }

        // Notifications - array of {message, type} objects (warning, success, info, error)
        $notifications = $scrapedProduct['notifications'] ?? [];
        if (! empty($notifications)) {
            $nlNotifications = [];
            $enNotifications = [];
            $deNotifications = [];
            $frNotifications = [];
            $esNotifications = [];

            foreach ($notifications as $notification) {
                $key = (string) Str::uuid();
                $nlMessage = $notification['message'] ?? '';
                $type = $notification['type'] ?? 'info';

                $nlNotifications[$key] = $notification;
                $enNotifications[$key] = ['message' => $this->getCachedTranslation($nlMessage, 'en'), 'type' => $type];
                $deNotifications[$key] = ['message' => $this->getCachedTranslation($nlMessage, 'de'), 'type' => $type];
                $frNotifications[$key] = ['message' => $this->getCachedTranslation($nlMessage, 'fr'), 'type' => $type];
                $esNotifications[$key] = ['message' => $this->getCachedTranslation($nlMessage, 'es'), 'type' => $type];
            }

            $data['notifications'] = new TranslatedRepeaterFieldValue([
                'nl' => $nlNotifications,
                'en' => $enNotifications,
                'de' => $deNotifications,
                'fr' => $frNotifications,
                'es' => $esNotifications,
            ]);
        }

        // Probo-specific attributes
        $this->updateProboAttributes($data, $scrapedProduct);

        return $data;
    }

    /**
     * Update Probo-specific attributes in the attribute data array.
     */
    protected function updateProboAttributes(array &$data, array $scrapedProduct): void
    {
        $menuLabel = $scrapedProduct['menu_label'] ?? null;
        if (! empty($menuLabel)) {
            $data['menu-label'] = new TranslatedText(collect([
                'nl' => $menuLabel,
                'en' => $this->getCachedTranslation($menuLabel, 'en'),
                'de' => $this->getCachedTranslation($menuLabel, 'de'),
                'fr' => $this->getCachedTranslation($menuLabel, 'fr'),
                'es' => $this->getCachedTranslation($menuLabel, 'es'),
            ]));
        }

        $menuDeliveryTime = $scrapedProduct['menu_delivery_time'] ?? null;
        if (! empty($menuDeliveryTime)) {
            $data['menu-delivery-time'] = new TranslatedText(collect([
                'nl' => (string) $menuDeliveryTime,
                'en' => (string) $menuDeliveryTime,
                'de' => (string) $menuDeliveryTime,
                'fr' => (string) $menuDeliveryTime,
                'es' => (string) $menuDeliveryTime,
            ]));
        }

        $menuPassportLabel = $scrapedProduct['menu_passport_label'] ?? null;
        if (! empty($menuPassportLabel)) {
            $data['menu-passport-label'] = new TranslatedText(collect([
                'nl' => $menuPassportLabel,
                'en' => $this->getCachedTranslation($menuPassportLabel, 'en'),
                'de' => $this->getCachedTranslation($menuPassportLabel, 'de'),
                'fr' => $this->getCachedTranslation($menuPassportLabel, 'fr'),
                'es' => $this->getCachedTranslation($menuPassportLabel, 'es'),
            ]));
        }

        // Delivery info title and subtitle
        $deliveryInfo = $scrapedProduct['delivery_info'] ?? [];
        if (! empty($deliveryInfo['title'])) {
            $nlTitle = $deliveryInfo['title'];
            $data['delivery-info-title'] = new TranslatedText(collect([
                'nl' => $nlTitle,
                'en' => $this->getCachedTranslation($nlTitle, 'en'),
                'de' => $this->getCachedTranslation($nlTitle, 'de'),
                'fr' => $this->getCachedTranslation($nlTitle, 'fr'),
                'es' => $this->getCachedTranslation($nlTitle, 'es'),
            ]));
        }
        if (! empty($deliveryInfo['subtitle'])) {
            $nlSubtitle = $deliveryInfo['subtitle'];
            $data['delivery-info-subtitle'] = new TranslatedText(collect([
                'nl' => $nlSubtitle,
                'en' => $this->getCachedTranslation($nlSubtitle, 'en'),
                'de' => $this->getCachedTranslation($nlSubtitle, 'de'),
                'fr' => $this->getCachedTranslation($nlSubtitle, 'fr'),
                'es' => $this->getCachedTranslation($nlSubtitle, 'es'),
            ]));
        }

        // Boolean flags
        $data['hide-pricelist'] = new Toggle($scrapedProduct['hide_pricelist'] ?? false);
        $data['has-sample'] = new Toggle($scrapedProduct['has_sample'] ?? false);

        // Article group name (dropdown)
        $articleGroupName = $scrapedProduct['article_group_name'] ?? null;
        if (! empty($articleGroupName)) {
            $data['article-group-name'] = new Dropdown($articleGroupName);
        }

        // Pinterest URL
        $pinterestUrl = $scrapedProduct['pinterest_url'] ?? null;
        if (! empty($pinterestUrl)) {
            $data['pinterest-url'] = new Text($pinterestUrl);
        }
    }

    /**
     * Generate a slug handle from an attribute name.
     */
    protected function generateAttributeHandle(string $name): string
    {
        return Str::slug($name);
    }

    /**
     * Ensure an attribute exists in the database and is attached to the product type.
     */
    protected function ensureAttributeExists(string $handle, string $name): void
    {
        $attribute = Attribute::where('attribute_type', 'product')
            ->where('handle', $handle)
            ->first();

        if (! $attribute) {
            // Determine which group to use
            $group = $this->isToggleField($handle) ? $this->featuresGroup : $this->specsGroup;

            $attribute = Attribute::create([
                'attribute_type' => 'product',
                'attribute_group_id' => $group->id,
                'handle' => $handle,
                'name' => ['en' => $name, 'nl' => $name],
                'type' => $this->guessFieldType($handle),
                'position' => Attribute::where('attribute_type', 'product')->max('position') + 1,
                'searchable' => true,
                'filterable' => $this->isFilterable($handle),
                'required' => false,
                'section' => 'main',
                'system' => false,
                'configuration' => [],
            ]);

            Log::info("Created new attribute: {$handle}", ['name' => $name, 'type' => $attribute->type]);
        }

        // Attach to product type if not already
        if (! $this->productType->mappedAttributes()->where('lunar_attributes.id', $attribute->id)->exists()) {
            $this->productType->mappedAttributes()->attach($attribute);
        }
    }

    /**
     * Create a field value based on the attribute handle and value.
     */
    protected function createFieldValue(string $handle, mixed $value): Text|Number|Toggle
    {
        if ($this->isToggleField($handle)) {
            // Convert various "yes" formats to boolean
            $boolValue = is_bool($value) ? $value :
                in_array(strtolower((string) $value), ['yes', 'ja', 'true', '1', '✓', '✔']);

            return new Toggle($boolValue);
        }

        if ($this->isNumberField($handle)) {
            // Extract numeric value
            $numValue = (int) preg_replace('/\D/', '', (string) $value);

            return new Number($numValue);
        }

        return new Text((string) $value);
    }

    /**
     * Guess the field type class based on handle.
     */
    protected function guessFieldType(string $handle): string
    {
        if ($this->isToggleField($handle)) {
            return Toggle::class;
        }

        if ($this->isNumberField($handle)) {
            return Number::class;
        }

        return Text::class;
    }

    /**
     * Check if a handle represents a toggle/boolean field.
     */
    protected function isToggleField(string $handle): bool
    {
        $togglePatterns = ['pvc-free', 'fire-certificate', 'wind-permeable', 'washable', 'recyclable'];

        if (in_array($handle, $togglePatterns)) {
            return true;
        }

        // Check for common toggle patterns
        return Str::contains($handle, ['-free', 'certificate', 'permeable', 'recyclable', 'waterproof']);
    }

    /**
     * Check if a handle represents a number field.
     */
    protected function isNumberField(string $handle): bool
    {
        $numberPatterns = ['width', 'height', 'weight', 'lead-time', 'thickness', 'days'];

        foreach ($numberPatterns as $pattern) {
            if (Str::contains($handle, $pattern)) {
                return true;
            }
        }

        // Check for common unit patterns
        return (bool) preg_match('/-(cm|mm|m2|g|kg|days?)$/', $handle);
    }

    /**
     * Check if an attribute should be filterable.
     */
    protected function isFilterable(string $handle): bool
    {
        $filterableHandles = [
            'base-material',
            'print-technology',
            'indoor-outdoor-use',
            'lifespan',
            'pvc-free',
            'fire-certificate',
            'washable',
            'recyclable',
        ];

        return in_array($handle, $filterableHandles);
    }

    /**
     * Get or create the Details attribute group.
     */
    protected function getDetailsAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'details'],
            ['name' => ['en' => 'Details', 'nl' => 'Details'], 'position' => 1]
        );
    }

    /**
     * Get or create the Delivery attribute group.
     */
    protected function getDeliveryAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'delivery'],
            ['name' => ['en' => 'Delivery', 'nl' => 'Levering'], 'position' => 2]
        );
    }

    /**
     * Get or create the SEO attribute group.
     */
    protected function getSeoAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'seo'],
            ['name' => ['en' => 'SEO', 'nl' => 'SEO'], 'position' => 3]
        );
    }

    /**
     * Get or create the Features attribute group.
     */
    protected function getFeaturesAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'features'],
            ['name' => ['en' => 'Features', 'nl' => 'Kenmerken'], 'position' => 4]
        );
    }

    /**
     * Get or create the Resources attribute group.
     */
    protected function getResourcesAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'resources'],
            ['name' => ['en' => 'Resources', 'nl' => 'Bronnen'], 'position' => 5]
        );
    }

    /**
     * Get or create the Probo attribute group.
     */
    protected function getProboAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['attributable_type' => 'product', 'handle' => 'probo'],
            ['name' => ['en' => 'Probo', 'nl' => 'Probo'], 'position' => 6]
        );
    }

    /**
     * Ensure Details attributes exist in the database.
     */
    protected function ensureDetailsAttributesExist(): void
    {
        $attributes = [
            'name' => [
                'name' => ['en' => 'Name', 'nl' => 'Naam'],
                'type' => TranslatedText::class,
                'required' => true,
            ],
            'description' => [
                'name' => ['en' => 'Description', 'nl' => 'Beschrijving'],
                'type' => TranslatedText::class,
                'configuration' => ['richtext' => true],
            ],
            'short-description' => [
                'name' => ['en' => 'Short Description', 'nl' => 'Korte Beschrijving'],
                'type' => TranslatedText::class,
            ],
        ];

        $this->createAttributesForGroup($this->detailsGroup, $attributes);
    }

    /**
     * Ensure Delivery attributes exist in the database.
     */
    protected function ensureDeliveryAttributesExist(): void
    {
        $attributes = [
            'delivery-title' => [
                'name' => ['en' => 'Delivery Title', 'nl' => 'Levering Titel'],
                'type' => TranslatedText::class,
            ],
            'delivery-subtitle' => [
                'name' => ['en' => 'Delivery Subtitle', 'nl' => 'Levering Ondertitel'],
                'type' => TranslatedText::class,
            ],
        ];

        $this->createAttributesForGroup($this->deliveryGroup, $attributes);
    }

    /**
     * Ensure SEO attributes exist in the database.
     */
    protected function ensureSeoAttributesExist(): void
    {
        $attributes = [
            'meta-title' => [
                'name' => ['en' => 'Meta Title', 'nl' => 'Meta Titel'],
                'type' => TranslatedText::class,
            ],
            'meta-description' => [
                'name' => ['en' => 'Meta Description', 'nl' => 'Meta Beschrijving'],
                'type' => TranslatedText::class,
            ],
            'meta-keywords' => [
                'name' => ['en' => 'Meta Keywords', 'nl' => 'Meta Trefwoorden'],
                'type' => Text::class,
            ],
        ];

        $this->createAttributesForGroup($this->seoGroup, $attributes);
    }

    /**
     * Ensure Features attributes exist in the database.
     */
    protected function ensureFeaturesAttributesExist(): void
    {
        $attributes = [
            'product-benefits' => [
                'name' => ['en' => 'Product Benefits', 'nl' => 'Productvoordelen'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'value', 'label' => 'Benefit', 'type' => 'text'],
                    ],
                    'item_label_field' => 'value',
                ],
            ],
            'product-pros' => [
                'name' => ['en' => 'Product Pros', 'nl' => 'Pluspunten'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'value', 'label' => 'Pro', 'type' => 'text'],
                    ],
                    'item_label_field' => 'value',
                ],
            ],
            'product-cons' => [
                'name' => ['en' => 'Product Cons', 'nl' => 'Minpunten'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'value', 'label' => 'Con', 'type' => 'text'],
                    ],
                    'item_label_field' => 'value',
                ],
            ],
        ];

        $this->createAttributesForGroup($this->featuresGroup, $attributes);
    }

    /**
     * Ensure Resources attributes exist in the database.
     * Downloads and FAQ use translatable field types with Repeater admin display per language.
     */
    protected function ensureResourcesAttributesExist(): void
    {
        $attributes = [
            'downloads' => [
                'name' => ['en' => 'Downloads', 'nl' => 'Downloads'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'url', 'label' => 'URL', 'type' => 'url'],
                    ],
                    'item_label_field' => 'title',
                ],
            ],
            'faq' => [
                'name' => ['en' => 'FAQ', 'nl' => 'Veelgestelde vragen'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'answer', 'label' => 'Answer', 'type' => 'text'],
                    ],
                    'item_label_field' => 'question',
                ],
            ],
            'specificaties' => [
                'name' => ['en' => 'Specifications', 'nl' => 'Specificaties'],
                'type' => TranslatedText::class,
            ],
            'option-alerts' => [
                'name' => ['en' => 'Option Alerts', 'nl' => 'Optie waarschuwingen'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'message', 'label' => 'Message', 'type' => 'text'],
                    ],
                    'item_label_field' => 'message',
                ],
            ],
            'notifications' => [
                'name' => ['en' => 'Notifications', 'nl' => 'Notificaties'],
                'type' => TranslatedRepeaterFieldValue::class,
                'configuration' => [
                    'schema' => [
                        ['name' => 'message', 'label' => 'Message', 'type' => 'text'],
                        ['name' => 'type', 'label' => 'Type', 'type' => 'text'],
                    ],
                    'item_label_field' => 'message',
                ],
            ],
        ];

        $this->createAttributesForGroup($this->resourcesGroup, $attributes);
    }

    /**
     * Ensure Probo-specific attributes exist in the database.
     */
    protected function ensureProboAttributesExist(): void
    {
        $attributes = [
            'menu-label' => [
                'name' => ['en' => 'Menu Label', 'nl' => 'Menu Label'],
                'type' => TranslatedText::class,
            ],
            'menu-delivery-time' => [
                'name' => ['en' => 'Menu Delivery Time', 'nl' => 'Menu Levertijd'],
                'type' => TranslatedText::class,
            ],
            'menu-passport-label' => [
                'name' => ['en' => 'Menu Passport Label', 'nl' => 'Menu Paspoort Label'],
                'type' => TranslatedText::class,
            ],
            'delivery-info-title' => [
                'name' => ['en' => 'Delivery Info Title', 'nl' => 'Levering Info Titel'],
                'type' => TranslatedText::class,
            ],
            'delivery-info-subtitle' => [
                'name' => ['en' => 'Delivery Info Subtitle', 'nl' => 'Levering Info Ondertitel'],
                'type' => TranslatedText::class,
            ],
            'hide-pricelist' => [
                'name' => ['en' => 'Hide Pricelist', 'nl' => 'Verberg Prijslijst'],
                'type' => Toggle::class,
            ],
            'has-sample' => [
                'name' => ['en' => 'Has Sample', 'nl' => 'Heeft Monster'],
                'type' => Toggle::class,
            ],
            'article-group-name' => [
                'name' => ['en' => 'Article Group', 'nl' => 'Artikelgroep'],
                'type' => Dropdown::class,
                'configuration' => [
                    'lookups' => [
                        ['label' => 'Accessory', 'value' => 'Accessory'],
                        ['label' => 'Auto Purchase', 'value' => 'Auto Purchase'],
                        ['label' => 'Composed product', 'value' => 'Composed product'],
                        ['label' => 'Flags', 'value' => 'Flags'],
                        ['label' => 'Foil', 'value' => 'Foil'],
                        ['label' => 'Grab Commodity', 'value' => 'Grab Commodity'],
                        ['label' => 'Material', 'value' => 'Material'],
                        ['label' => 'Paper', 'value' => 'Paper'],
                        ['label' => 'Picking Commodity', 'value' => 'Picking Commodity'],
                        ['label' => 'Sheet', 'value' => 'Sheet'],
                    ],
                ],
            ],
            'pinterest-url' => [
                'name' => ['en' => 'Pinterest URL', 'nl' => 'Pinterest URL'],
                'type' => Text::class,
            ],
        ];

        $this->createAttributesForGroup($this->proboGroup, $attributes);
    }

    /**
     * Create attributes for a given group.
     */
    protected function createAttributesForGroup(AttributeGroup $group, array $attributes): void
    {
        $position = Attribute::where('attribute_type', 'product')->max('position') ?? 0;

        foreach ($attributes as $handle => $config) {
            $attribute = Attribute::firstOrCreate(
                ['attribute_type' => 'product', 'handle' => $handle],
                [
                    'attribute_group_id' => $group->id,
                    'name' => $config['name'],
                    'type' => $config['type'],
                    'position' => ++$position,
                    'searchable' => $config['searchable'] ?? false,
                    'filterable' => $config['filterable'] ?? false,
                    'required' => $config['required'] ?? false,
                    'section' => $config['section'] ?? 'main',
                    'system' => $config['system'] ?? false,
                    'configuration' => $config['configuration'] ?? [],
                ]
            );

            // Update configuration if provided and attribute exists but has empty config
            if (! empty($config['configuration']) && empty($attribute->configuration?->toArray())) {
                $attribute->update(['configuration' => $config['configuration']]);
            }

            // Attach to product type if not already
            if (! $this->productType->mappedAttributes()->where('lunar_attributes.id', $attribute->id)->exists()) {
                $this->productType->mappedAttributes()->attach($attribute);
            }
        }
    }

    protected function getProductType(): ProductType
    {
        return ProductType::firstOrCreate(
            ['name' => 'Probo Supplier Product'],
            ['name' => 'Probo Supplier Product']
        );
    }

    protected function parsePrice(?string $price): int
    {
        if (! $price) {
            return 0;
        }

        // Remove currency symbols and whitespace
        $price = preg_replace('/[^0-9.,]/', '', $price);

        // Handle European format (comma as decimal separator)
        if (str_contains($price, ',') && ! str_contains($price, '.')) {
            $price = str_replace(',', '.', $price);
        } elseif (str_contains($price, ',') && str_contains($price, '.')) {
            // Format like 1.234,56 -> remove dots, convert comma
            $price = str_replace('.', '', $price);
            $price = str_replace(',', '.', $price);
        }

        // Convert to cents
        return (int) round((float) $price * $this->currency->factor);
    }

    /**
     * Replace Probo URLs with the app URL.
     */
    protected function replaceProboUrls(?string $content): ?string
    {
        if (! $content) {
            return $content;
        }

        $appUrl = rtrim(config('app.url'), '/');

        // Replace probo.nl URLs with app URL
        $content = preg_replace(
            '#https?://(www\.)?probo\.nl#i',
            $appUrl,
            $content
        );

        return $content;
    }

    /**
     * Replace Probo brand references with the app name.
     */
    protected function replaceProboReferences(?string $content): ?string
    {
        if (! $content) {
            return $content;
        }

        $appName = config('app.name', 'Print4Sign');

        // Replace "Probo" with app name (case-insensitive, preserve surrounding context)
        $content = preg_replace('/\bProbo\b/i', $appName, $content);

        return $content;
    }

    /**
     * Apply all content transformations (URLs and brand references).
     */
    protected function transformContent(?string $content): ?string
    {
        $content = $this->replaceProboUrls($content);
        $content = $this->replaceProboReferences($content);

        return $content;
    }

    /**
     * Rewrite description content using Claude AI.
     * Returns original HTML with brand/URL replacements, or AI-rewritten HTML if Claude is available.
     */
    public function rewriteDescriptionWithClaude(?string $description, string $productName): ?string
    {
        if (empty($description)) {
            return $description;
        }

        // First apply basic transformations
        $description = $this->transformContent($description);

        // If no Claude client, return the transformed content
        if (! $this->claudeClient) {
            $this->claudeClient = new ClaudeClient;
        }

        // Use Claude to rewrite the description
        $rewritten = $this->claudeClient->rewriteDescription($description, $productName);

        if (empty($rewritten) || $rewritten === $description) {
            Log::info("Claude rewrite returned original content for: {$productName}");

            return $description;
        }

        Log::info("Claude rewrote description for: {$productName}", [
            'original_length' => strlen($description),
            'rewritten_length' => strlen($rewritten),
        ]);

        return $rewritten;
    }

    /**
     * Translate content to a target locale using Claude AI.
     *
     * @param  string|null  $content  The content to translate
     * @param  string  $targetLocale  The target locale (en, de, fr)
     * @param  string  $sourceLocale  The source locale (defaults to 'nl')
     */
    public function translateTo(?string $content, string $targetLocale, string $sourceLocale = 'nl'): ?string
    {
        if (empty($content) || $sourceLocale === $targetLocale) {
            return $content;
        }

        // Check cache first
        $cacheKey = md5($content);
        if (isset($this->translationCache[$targetLocale][$cacheKey])) {
            return $this->translationCache[$targetLocale][$cacheKey];
        }

        if (! $this->claudeClient) {
            $this->claudeClient = new ClaudeClient;
        }

        $translated = $this->claudeClient->translateContent($content, $sourceLocale, $targetLocale);

        if (empty($translated) || $translated === $content) {
            return $content;
        }

        // Store in cache
        $this->translationCache[$targetLocale][$cacheKey] = $translated;

        Log::info("Translated content from {$sourceLocale} to {$targetLocale}", [
            'original_length' => strlen($content),
            'translated_length' => strlen($translated),
        ]);

        return $translated;
    }

    /**
     * Batch translate all texts for a product to all target languages.
     * This significantly reduces API calls by translating multiple texts per language in a single request.
     *
     * @param  array  $textsToTranslate  Associative array of key => text to translate
     * @param  string  $sourceLocale  Source language code
     */
    protected function batchTranslateForProduct(array $textsToTranslate, string $sourceLocale = 'nl'): void
    {
        if (empty($textsToTranslate)) {
            return;
        }

        // Filter out empty/null values
        $textsToTranslate = array_filter($textsToTranslate, fn ($t) => ! empty($t));

        if (empty($textsToTranslate)) {
            return;
        }

        if (! $this->claudeClient) {
            $this->claudeClient = new ClaudeClient;
        }

        $targetLocales = ['en', 'de', 'fr', 'es'];

        foreach ($targetLocales as $targetLocale) {
            if ($targetLocale === $sourceLocale) {
                continue;
            }

            // Filter out texts already in cache
            $toTranslate = [];
            foreach ($textsToTranslate as $key => $text) {
                $cacheKey = md5($text);
                if (! isset($this->translationCache[$targetLocale][$cacheKey])) {
                    $toTranslate[$key] = $text;
                }
            }

            if (empty($toTranslate)) {
                continue;
            }

            Log::info("Batch translating ".count($toTranslate)." texts to {$targetLocale}");

            $translated = $this->claudeClient->translateBatch($toTranslate, $sourceLocale, $targetLocale);

            // Store results in cache
            foreach ($translated as $key => $translatedText) {
                if (isset($textsToTranslate[$key])) {
                    $originalText = $textsToTranslate[$key];
                    $cacheKey = md5($originalText);
                    $this->translationCache[$targetLocale][$cacheKey] = $translatedText;
                }
            }
        }
    }

    /**
     * Get a cached translation or fall back to the original text.
     */
    protected function getCachedTranslation(?string $content, string $targetLocale): ?string
    {
        if (empty($content)) {
            return $content;
        }

        $cacheKey = md5($content);

        return $this->translationCache[$targetLocale][$cacheKey] ?? $content;
    }

    /**
     * Clear the translation cache (called at the start of each product import).
     */
    protected function clearTranslationCache(): void
    {
        $this->translationCache = [];
    }

    /**
     * Collect all Dutch texts from scraped product that need translation.
     * Only collects texts where no translation already exists in the scraped data.
     *
     * @return array<string, string> Key => Dutch text to translate
     */
    protected function collectTextsForTranslation(array $scrapedProduct, array $translations): array
    {
        $texts = [];
        $enData = $translations['en'] ?? [];
        $nlData = $translations['nl'] ?? [];

        // Name - only if no English translation exists
        $nlName = $nlData['title'] ?? $scrapedProduct['name'] ?? null;
        if ($nlName && empty($enData['title'])) {
            $texts['name'] = $nlName;
        }

        // Description
        $scrapedDesc = $scrapedProduct['description'] ?? '';
        $translationDesc = $nlData['description'] ?? '';
        $descSource = (strlen($scrapedDesc) > strlen($translationDesc) + 50)
            ? $scrapedDesc
            : ($translationDesc ?: $scrapedDesc);
        $nlDesc = $this->transformContent($descSource);
        if ($nlDesc && empty($this->transformContent($enData['description'] ?? ''))) {
            $texts['description'] = $nlDesc;
        }

        // Short description
        $nlShort = $nlData['short_description'] ?? $scrapedProduct['short_description'] ?? '';
        if ($nlShort && empty($enData['short_description'])) {
            $texts['short_description'] = $nlShort;
        }

        // Delivery info
        $deliveryInfo = $scrapedProduct['delivery_info'] ?? [];
        if (! empty($deliveryInfo['title'])) {
            $texts['delivery_title'] = $deliveryInfo['title'];
        }
        if (! empty($deliveryInfo['subtitle'])) {
            $texts['delivery_subtitle'] = $deliveryInfo['subtitle'];
        }

        // SEO
        $seo = $scrapedProduct['seo'] ?? [];
        if (! empty($seo['title'])) {
            $texts['seo_title'] = $this->transformContent($seo['title']);
        }
        if (! empty($seo['description'])) {
            $texts['seo_description'] = $this->transformContent($seo['description']);
        }

        // Downloads titles
        foreach ($scrapedProduct['downloads'] ?? [] as $i => $item) {
            if (! empty($item['title'])) {
                $texts["download_{$i}"] = $item['title'];
            }
        }

        // FAQ questions and answers
        foreach ($scrapedProduct['faq'] ?? [] as $i => $item) {
            if (! empty($item['question'])) {
                $texts["faq_q_{$i}"] = $item['question'];
            }
            if (! empty($item['answer'])) {
                $texts["faq_a_{$i}"] = $item['answer'];
            }
        }

        // Option alerts
        foreach ($scrapedProduct['option_alerts'] ?? [] as $i => $alert) {
            if (! empty($alert)) {
                $texts["alert_{$i}"] = $alert;
            }
        }

        // Notifications
        foreach ($scrapedProduct['notifications'] ?? [] as $i => $notification) {
            if (! empty($notification['message'])) {
                $texts["notification_{$i}"] = $notification['message'];
            }
        }

        // Probo-specific
        if (! empty($scrapedProduct['menu_label'])) {
            $texts['menu_label'] = $scrapedProduct['menu_label'];
        }
        if (! empty($scrapedProduct['menu_passport_label'])) {
            $texts['menu_passport_label'] = $scrapedProduct['menu_passport_label'];
        }

        return $texts;
    }

    /**
     * Translate content from Dutch to English using Claude AI.
     *
     * @deprecated Use translateTo() instead
     */
    public function translateToEnglish(?string $content): ?string
    {
        return $this->translateTo($content, 'en', 'nl');
    }

    /**
     * Generate a stable external ID from the scraped product data.
     * Prioritizes URL-based ID since SKU can change based on login state.
     */
    protected function generateExternalId(array $scrapedProduct): ?string
    {
        // First, try to use the URL path as it's the most stable
        if (! empty($scrapedProduct['url'])) {
            $path = parse_url($scrapedProduct['url'], PHP_URL_PATH);
            if ($path) {
                // Remove leading/trailing slashes and convert to slug
                return trim($path, '/');
            }
        }

        // Fall back to scraped external_id (usually derived from URL)
        if (! empty($scrapedProduct['external_id'])) {
            return $scrapedProduct['external_id'];
        }

        // Fall back to product_id from the page
        if (! empty($scrapedProduct['product_id']) || ! empty($scrapedProduct['meta']['product_id'])) {
            return $scrapedProduct['product_id'] ?? $scrapedProduct['meta']['product_id'];
        }

        // Last resort: use SKU (but this can change with login state)
        return $scrapedProduct['sku'] ?? null;
    }

    /**
     * Download images from URLs and attach them to the Product's media library.
     * If images already exist locally, they are attached without re-downloading.
     *
     * @param  Product  $product  The Lunar product to attach images to
     * @param  array  $images  Array of image data (either URLs or {url, alt} objects)
     */
    protected function downloadAndAttachImages(Product $product, array $images): void
    {
        if (! $this->imageDownloader) {
            $this->imageDownloader = new ImageDownloader();
        }

        // Handle existing images based on updateImages flag
        $existingImageCount = $product->getMedia('images')->count();
        if ($existingImageCount > 0) {
            if (! $this->updateImages) {
                Log::info("Product already has images, skipping", [
                    'product_id' => $product->id,
                    'existing_images' => $existingImageCount,
                ]);

                return;
            }

            // Remove existing images for update
            Log::info("Updating images - removing existing", [
                'product_id' => $product->id,
                'removing_count' => $existingImageCount,
            ]);
            $product->clearMediaCollection('images');
        }

        $productName = $product->translateAttribute('name') ?? 'product';
        $prefix = Str::slug($productName);

        Log::info("Processing ".count($images)." images for: {$productName}");

        $successCount = 0;
        $skippedCount = 0;
        $failCount = 0;

        foreach ($images as $index => $imageData) {
            // Handle both formats: plain URL string or {url, alt} object
            $url = is_array($imageData) ? ($imageData['url'] ?? null) : $imageData;
            $alt = is_array($imageData) ? ($imageData['alt'] ?? '') : '';

            if (empty($url)) {
                continue;
            }

            // Skip mockup images
            $lowerUrl = strtolower($url);
            if (str_contains($lowerUrl, 'mock up') || str_contains($lowerUrl, 'mock-up') || str_contains($lowerUrl, 'mockup') || str_contains($lowerUrl, 'mock%20up')) {
                Log::info("Skipping mockup image", ['url' => $url]);
                continue;
            }

            try {
                // Generate filename with prefix and index
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                $filename = $prefix.'-'.($index + 1).'.'.$extension;

                // Check if file already exists locally
                $existedLocally = $this->imageDownloader->exists($filename);

                // Download the image (or get existing local URL)
                $localUrl = $this->imageDownloader->download($url, $filename);

                if ($localUrl) {
                    // Get the full path using the ImageDownloader
                    $fullPath = $this->imageDownloader->getFullPath($filename);

                    if (file_exists($fullPath)) {
                        // Add to media library with alt text and custom properties
                        $media = $product->addMedia($fullPath)
                            ->preservingOriginal()
                            ->withCustomProperties([
                                'alt' => $alt,
                                'original_url' => $url,
                            ])
                            ->toMediaCollection('images');

                        // Set the name (used as alt text in some contexts)
                        if ($alt) {
                            $media->update(['name' => $alt]);
                        }

                        if ($existedLocally) {
                            $skippedCount++;
                            Log::info("Attached existing local image to product", [
                                'product_id' => $product->id,
                                'media_id' => $media->id,
                                'filename' => $filename,
                            ]);
                        } else {
                            $successCount++;
                            Log::info("Downloaded and attached image to product", [
                                'product_id' => $product->id,
                                'media_id' => $media->id,
                                'alt' => $alt,
                            ]);
                        }
                    }
                } else {
                    $failCount++;
                    Log::warning("Failed to get image", [
                        'product_id' => $product->id,
                        'url' => $url,
                    ]);
                }
            } catch (FileDoesNotExist|FileIsTooBig $e) {
                $failCount++;
                Log::error("Media library error attaching image", [
                    'product_id' => $product->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Exception $e) {
                $failCount++;
                Log::error("Error processing image", [
                    'product_id' => $product->id,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Image processing complete for: {$productName}", [
            'product_id' => $product->id,
            'downloaded' => $successCount,
            'used_existing' => $skippedCount,
            'failed' => $failCount,
            'total' => count($images),
        ]);

        // Regenerate media conversions for newly added images
        if ($successCount > 0 || $skippedCount > 0) {
            $this->regenerateMediaConversions($product);
        }
    }

    /**
     * Regenerate media conversions for a product's images.
     */
    protected function regenerateMediaConversions(Product $product): void
    {
        $mediaIds = $product->getMedia('images')->pluck('id')->toArray();

        if (empty($mediaIds)) {
            return;
        }

        try {
            // Regenerate conversions (queues jobs)
            Artisan::call('media-library:regenerate', [
                '--ids' => implode(',', $mediaIds),
                '--force' => true,
            ]);

            // Process the queued conversion jobs immediately
            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--queue' => 'default',
            ]);

            Log::info("Generated media conversions for product: {$product->id}", [
                'media_count' => count($mediaIds),
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to generate conversions for product: {$product->id}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Save specifications HTML to the input folder.
     *
     * @param  Product  $product  The product to save specifications for
     * @param  string  $html  The raw specifications HTML
     * @return string|null  The slug for view resolution or null on failure
     */
    protected function saveSpecificationsHtml(Product $product, string $html): ?string
    {
        $inputDir = storage_path('app/scraper/specifications/input');

        if (! is_dir($inputDir)) {
            mkdir($inputDir, 0755, true);
        }

        // Get the product slug for the filename
        $slug = $product->defaultUrl?->slug ?? Str::slug($product->translateAttribute('name') ?? "product-{$product->id}");
        $inputPath = "{$inputDir}/{$slug}.html";

        try {
            file_put_contents($inputPath, $html);

            Log::info("Saved specifications input HTML", [
                'product_id' => $product->id,
                'slug' => $slug,
                'path' => $inputPath,
                'size' => strlen($html),
            ]);

            return $slug;
        } catch (\Exception $e) {
            Log::error("Failed to save specifications file", [
                'product_id' => $product->id,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Normalize unit code from scraped data to match UnitCode enum values.
     * Converts display symbols (m², cm²) to enum values (m2, cm2).
     */
    protected function normalizeUnitCode(?string $unitCode): ?string
    {
        if (! $unitCode) {
            return null;
        }

        // Mapping of display symbols to enum values
        $mapping = [
            'm²' => 'm2',
            'cm²' => 'cm2',
            'M²' => 'm2',
            'CM²' => 'cm2',
            'stuks' => 'pc',
            'stuk' => 'pc',
            'Stuk' => 'pc',
            'Stuks' => 'pc',
            'st.' => 'pc',
            'St.' => 'pc',
            'pcs' => 'pc',
            'Pcs' => 'pc',
        ];

        // Return mapped value or original value (lowercase)
        return $mapping[$unitCode] ?? strtolower($unitCode);
    }

    /**
     * Create URLs for all languages with translated slugs.
     */
    protected function createMultilingualUrls(Product $product, array $attributeData, string $fallbackName): void
    {
        $languages = Language::all();
        $defaultLanguage = Language::getDefault();
        $nameAttribute = $attributeData['name'] ?? null;

        foreach ($languages as $language) {
            $translatedName = null;

            // Get translated name for this language
            // TranslatedText->getValue() returns a Collection with language codes as keys
            // Each value is a Text instance, so we need ->getValue() again to get the string
            if ($nameAttribute instanceof TranslatedText) {
                $textValue = $nameAttribute->getValue()->get($language->code);
                if ($textValue) {
                    $translatedName = $textValue->getValue();
                }
            }

            // Fallback to original name
            $translatedName = $translatedName ?: $fallbackName;

            $slug = Str::slug($translatedName);
            $uniqueSlug = $this->getUniqueSlug($slug, $language->id);

            $product->urls()->create([
                'language_id' => $language->id,
                'slug' => $uniqueSlug,
                'default' => $language->id === $defaultLanguage?->id,
            ]);

            Log::debug("Created URL for {$language->code}: {$uniqueSlug}");
        }
    }

    /**
     * Get a unique slug for the given language.
     */
    protected function getUniqueSlug(string $slug, int $languageId): string
    {
        $existingCount = Url::where('slug', $slug)
            ->where('language_id', $languageId)
            ->count();

        if ($existingCount === 0) {
            return $slug;
        }

        // Find unique suffix
        $suffix = 2;
        while (Url::where('slug', "{$slug}-{$suffix}")->where('language_id', $languageId)->exists()) {
            $suffix++;
        }

        return "{$slug}-{$suffix}";
    }
}
