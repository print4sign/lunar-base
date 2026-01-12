<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Lunar\Base\Contracts\ProvidesUploadSpec;
use Lunar\Facades\CartSession;
use Lunar\Facades\Suppliers;
use Lunar\Models\ProductVariant;
use Lunar\Models\SupplierProduct;
use Lunar\Services\ProboConfiguratorService;

class ProboConfigurator extends Component
{

    /**
     * The supplier product ID being configured.
     */
    #[Locked]
    public ?int $supplierProductId = null;

    /**
     * The product variant ID.
     */
    #[Locked]
    public ?int $productVariantId = null;

    /**
     * Current option selections.
     */
    public array $selections = [];

    /**
     * Current quantity.
     */
    public int $quantity = 1;

    /**
     * Loading state.
     */
    public bool $isLoading = false;

    /**
     * Current configuration response.
     */
    public ?array $configurationData = null;

    /**
     * All known options from initialization.
     */
    public array $allKnownOptions = [];

    /**
     * Current pricing data.
     */
    public ?array $pricingData = null;

    /**
     * Error message if any.
     */
    public ?string $errorMessage = null;

    /**
     * Whether item was added to cart.
     */
    public bool $added = false;

    /**
     * The configurator service instance.
     */
    protected ?ProboConfiguratorService $service = null;

    /**
     * Dimension codes that should be treated as a group.
     */
    private const DIMENSION_CODES = ['width', 'height', 'length'];

    /**
     * Prefix used to prevent PHP from converting numeric string keys to integers.
     * PHP arrays automatically convert keys like "300" to int 0.
     */
    private const NUMERIC_KEY_PREFIX = '_n:';

    /**
     * Wrap a key to prevent PHP numeric string conversion.
     * "300" becomes "_n:300" to preserve it as a string key.
     */
    private function wrapKey(string $key): string
    {
        // Only wrap if the key is numeric (would be converted by PHP)
        if (ctype_digit($key)) {
            return self::NUMERIC_KEY_PREFIX . $key;
        }
        return $key;
    }

    /**
     * Unwrap a key that was wrapped to prevent numeric conversion.
     * "_n:300" becomes "300".
     */
    private function unwrapKey(string $key): string
    {
        if (str_starts_with($key, self::NUMERIC_KEY_PREFIX)) {
            return substr($key, strlen(self::NUMERIC_KEY_PREFIX));
        }
        return $key;
    }

    /**
     * Get a selection value by key, handling wrapped numeric keys.
     * Public so it can be used in Blade templates.
     */
    public function getSelection(string $key): mixed
    {
        // Try direct key first
        if (isset($this->selections[$key])) {
            return $this->selections[$key];
        }

        // Try wrapped key for numeric strings
        $wrappedKey = $this->wrapKey($key);
        if ($wrappedKey !== $key && isset($this->selections[$wrappedKey])) {
            return $this->selections[$wrappedKey];
        }

        return null;
    }

    /**
     * Check if a selection exists for the given key.
     * Handles wrapped numeric keys.
     */
    public function hasSelection(string $key): bool
    {
        return $this->getSelection($key) !== null;
    }

    /**
     * Get the selected choice value from a group of choices.
     * Returns the first choice value that is found in selections,
     * but only if it's not also a choice value in an earlier option group
     * (which would indicate it's a selection from a different group).
     *
     * @param  array  $choices  The choices for this option
     * @param  string|null  $optionCode  The option group code (used to avoid false positives from earlier groups)
     */
    public function getSelectedChoiceValue(array $choices, ?string $optionCode = null): ?string
    {
        foreach ($choices as $choice) {
            $value = $choice['value'] ?? null;
            if ($value === null || ! $this->hasSelection($value)) {
                continue;
            }

            // If no optionCode provided, return the first match (legacy behavior)
            if ($optionCode === null) {
                return $value;
            }

            // Check if this value is ALSO a choice in an earlier option group
            // If so, it's likely a selection from that earlier group, not this one
            if ($this->isChoiceFromEarlierGroup($optionCode, $value)) {
                continue;
            }

            return $value;
        }

        return null;
    }

    /**
     * Check if a choice value belongs to an option group that comes before the given group.
     */
    private function isChoiceFromEarlierGroup(string $currentGroupCode, string $choiceValue): bool
    {
        $currentIndex = $this->findOptionIndexByCode($currentGroupCode);

        if ($currentIndex === null) {
            return false;
        }

        // Look through all options BEFORE the current one
        for ($i = 0; $i < $currentIndex && $i < count($this->allKnownOptions); $i++) {
            $option = $this->allKnownOptions[$i];
            if (empty($option['choices'])) {
                continue;
            }

            foreach ($option['choices'] as $choice) {
                if (($choice['value'] ?? null) === $choiceValue) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Find the index of an option by its code (not by choice value).
     * This is more reliable when there are code collisions in choices.
     */
    private function findOptionIndexByCode(string $code): ?int
    {
        foreach ($this->allKnownOptions as $index => $option) {
            if (($option['code'] ?? null) === $code) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Mount the component.
     */
    public function mount(?int $supplierProductId = null, ?int $productVariantId = null): void
    {
        $this->supplierProductId = $supplierProductId;
        $this->productVariantId = $productVariantId;

        if ($this->supplierProductId) {
            // Check if variant has existing configuration to preload
            if ($this->productVariantId) {
                $variant = ProductVariant::find($this->productVariantId);
                if ($variant && ! empty($variant->configuration)) {
                    $this->initializeWithConfiguration($variant->configuration);
                    return;
                }
            }
            $this->initialize();
        }
    }

    /**
     * Initialize the configurator.
     */
    public function initialize(): void
    {
        if (! $this->supplierProductId) {
            return;
        }

        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $response = $this->getService()->initialize();

            $this->configurationData = $response->toArray();
            $this->selections = $response->selectedOptions;
            $this->allKnownOptions = $this->configurationData['available_options'] ?? [];

            // Only update pricing if configuration is orderable
            if ($response->canOrder) {
                $this->updatePricing();
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Initialize the configurator with an existing configuration.
     */
    protected function initializeWithConfiguration(array $configuration): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            // Remove internal keys
            $selections = array_filter($configuration, function ($value, $key) {
                return $key !== 'hash' && ! str_ends_with($key, '_skip');
            }, ARRAY_FILTER_USE_BOTH);

            // Extract quantity
            if (isset($selections['amount'])) {
                $this->quantity = (int) $selections['amount'];
            }

            // Step-by-step: apply each selection in order
            $this->allKnownOptions = [];
            $currentSelections = [];
            $response = null;

            // Get initial options
            $service = $this->createFreshService();
            $response = $service->configure([]);
            $this->mergeNewOptions($response->availableOptions);

            // Apply each saved selection in order
            foreach ($selections as $key => $value) {
                $currentSelections[$key] = $value;
                $service = $this->createFreshService();
                $service->setSelections($currentSelections);

                try {
                    $response = $service->configure([]);
                    $this->mergeNewOptions($response->availableOptions);
                } catch (\Exception $e) {
                    // Continue with remaining selections
                }
            }

            if ($response) {
                $this->configurationData = $response->toArray();
            }
            $this->selections = $selections;

            // Only update pricing if configuration is orderable
            if ($this->configurationData['can_order'] ?? false) {
                $this->updatePricing();
            }
        } catch (\Exception $e) {
            $this->errorMessage = null;
            $this->initialize();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Handle option selection.
     *
     * @param  string  $key  The option key/code
     * @param  mixed  $value  The option value
     * @param  string|null  $groupCode  Optional group code for radio options (used to find correct option index)
     */
    public function selectOption(string $key, mixed $value, ?string $groupCode = null): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        $isDimensionChange = in_array($key, self::DIMENSION_CODES);

        try {
            if (! $isDimensionChange || $this->hasBothDimensions()) {
                // Use group code if provided (for radio options), otherwise use key
                $this->clearSubsequentOptions($groupCode ?? $key);
            }

            // Wrap key to prevent PHP from converting numeric strings to integers
            $storageKey = $this->wrapKey($key);
            $this->selections[$storageKey] = $value;
            $this->clearCrossSellSkipFlag($key);

            // Wait for both dimensions before calling API
            if ($isDimensionChange && ! $this->hasBothValidDimensions()) {
                $this->isLoading = false;
                return;
            }

            $apiSelections = $this->getApiSelections();
            $response = $this->getService()->configure($apiSelections);
            $this->processApiResponse($response);

            // Only update pricing if configuration is orderable
            if ($response->canOrder) {
                $this->updatePricing();
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Handle radio option selection (only one choice can be selected per group).
     * Removes other choices from the same group before selecting the new one.
     *
     * For Probo API, radio/select options expect the child option code as both key and value.
     * Example: When selecting "straight-back-wall" from "stand-type" group,
     * the API expects: ['straight-back-wall' => 'straight-back-wall']
     *
     * The groupCode is used to:
     * 1. Clear other choices from the same group
     * 2. Find the correct option index for clearing subsequent options (avoids code collisions)
     */
    public function selectRadioOption(string $groupCode, string $selectedValue, array $allChoiceValues): void
    {
        // Only remove choices from THIS group that are NOT used by earlier groups
        // This prevents removing a selection like "flag" (material) when the composition
        // group also has "flag" as a choice
        $choicesToRemove = $this->getChoicesUniqueToGroup($groupCode, $allChoiceValues);

        foreach ($choicesToRemove as $choiceValue) {
            $wrappedKey = $this->wrapKey($choiceValue);
            unset($this->selections[$wrappedKey]);
        }

        // Use selected choice value as both key and value
        // The Probo API expects the child code: ['straight-back-wall' => 'straight-back-wall']
        // Pass the group code so clearSubsequentOptions finds the correct option by group, not by choice value
        $this->selectOption($selectedValue, $selectedValue, $groupCode);
    }

    /**
     * Get choices that are unique to this group and safe to remove.
     * Excludes any choice values that are also selections from earlier groups.
     */
    private function getChoicesUniqueToGroup(string $groupCode, array $allChoiceValues): array
    {
        $groupIndex = $this->findOptionIndexByCode($groupCode);

        if ($groupIndex === null) {
            return $allChoiceValues;
        }

        // Collect all choice values from groups BEFORE this one
        $earlierChoiceValues = [];
        for ($i = 0; $i < $groupIndex && $i < count($this->allKnownOptions); $i++) {
            $option = $this->allKnownOptions[$i];
            if (! empty($option['choices'])) {
                foreach ($option['choices'] as $choice) {
                    if (! empty($choice['value'])) {
                        $earlierChoiceValues[] = $choice['value'];
                    }
                }
            }
        }

        // Only return choices that are NOT in earlier groups
        return array_filter($allChoiceValues, function ($choice) use ($earlierChoiceValues) {
            return ! in_array($choice, $earlierChoiceValues);
        });
    }

    /**
     * Select both dimensions at once (for preset sizes).
     */
    public function selectDimensions(float $width, float $height): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $this->clearSubsequentOptions('height');
            $this->selections['width'] = $width;
            $this->selections['height'] = $height;

            $apiSelections = $this->getApiSelections();
            $response = $this->getService()->configure($apiSelections);
            $this->processApiResponse($response);

            // Only update pricing if configuration is orderable
            if ($response->canOrder) {
                $this->updatePricing();
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Skip all cross-sell products in a group.
     */
    public function skipCrossSellGroup(string $groupCode): void
    {
        foreach ($this->allKnownOptions as $option) {
            if (($option['type'] ?? '') === 'cross_sell' && ($option['group_code'] ?? '') === $groupCode) {
                unset($this->selections[$option['code']]);
            }
        }
        $this->selections[$groupCode . '_skip'] = 'skip';

        // Only update pricing if configuration is orderable
        if ($this->configurationData['can_order'] ?? false) {
            $this->updatePricing();
        }
    }

    /**
     * Select a cross-sell product with amount.
     */
    public function selectCrossSell(string $code, int $amount): void
    {
        $crossSellOption = $this->findOptionByCodeAndType($code, 'cross_sell');
        if (! $crossSellOption) {
            return;
        }

        if (isset($crossSellOption['group_code'])) {
            unset($this->selections[$crossSellOption['group_code'] . '_skip']);
        }

        if ($amount > 0) {
            $this->selections[$code] = $amount;
        } else {
            unset($this->selections[$code]);
        }

        // Only update pricing if configuration is orderable
        if ($this->configurationData['can_order'] ?? false) {
            $this->updatePricing();
        }
    }

    /**
     * Update quantity.
     */
    public function updatedQuantity(): void
    {
        $this->quantity = max(1, $this->quantity);
        $this->selections['quantity'] = $this->quantity;

        // Only update pricing if configuration is orderable
        if ($this->supplierProductId && ($this->configurationData['can_order'] ?? false)) {
            $this->updatePricing();
        }
    }

    /**
     * Increment quantity.
     */
    public function incrementQuantity(): void
    {
        $this->quantity++;
        $this->updatedQuantity();
    }

    /**
     * Decrement quantity.
     */
    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
            $this->updatedQuantity();
        }
    }

    /**
     * Add configured product to cart.
     */
    public function addToCart(): void
    {
        if (! $this->canOrder || ! $this->productVariantId) {
            return;
        }

        $this->isLoading = true;

        try {
            $cart = CartSession::manager();
            $variant = ProductVariant::find($this->productVariantId);

            if (! $variant) {
                $this->errorMessage = 'Variant niet gevonden';
                return;
            }

            // Use amount from selections (Probo uses 'amount'), fallback to quantity property
            $quantity = (int) ($this->selections['amount'] ?? $this->quantity ?? 1);

            // Build cart line meta
            $meta = [
                'supplier_configuration' => $this->getApiSelections(),
                'supplier_product_id' => $this->supplierProductId,
                'configured_price' => $this->pricingData['sell_price'] ?? null,
                'is_dynamic' => $variant->isDynamic(),
                'configuration_display' => $this->getConfigurationDisplay(),
            ];

            // Store calculation_id from Probo API for uploader session creation
            $calculationId = $this->extractCalculationId();
            if ($calculationId) {
                $meta['calculation_id'] = $calculationId;
            }

            // Resolve and store upload spec to avoid slow API calls later
            $uploadSpec = $this->resolveUploadSpecForCart($variant);
            if ($uploadSpec) {
                $meta['upload_spec'] = $uploadSpec;
            }

            $cart->add($variant, $quantity, $meta);

            $this->dispatch('cart-updated');
            $this->dispatch('toggle-cart');

            $this->added = true;

            $this->js('setTimeout(() => $wire.set("added", false), 2000)');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Extract the calculation_id from the configuration data.
     *
     * The calculation_id is required for creating Probo uploader sessions.
     * It comes from the Probo /products/configure API response (not /price).
     */
    protected function extractCalculationId(): ?string
    {
        // The calculation_id comes from the configure response, stored in configurationData
        if (! $this->configurationData) {
            return null;
        }

        // Check meta['calculation_id'] - this is the primary location after the fix
        $meta = $this->configurationData['meta'] ?? [];
        if (! empty($meta['calculation_id'])) {
            return (string) $meta['calculation_id'];
        }

        // Fallback: check in raw_response (in case the meta structure changes)
        $rawResponse = $meta['raw_response'] ?? [];
        if (! empty($rawResponse['calculation_id'])) {
            return (string) $rawResponse['calculation_id'];
        }

        return null;
    }

    /**
     * Resolve upload spec for storing in cart line meta.
     */
    protected function resolveUploadSpecForCart(ProductVariant $variant): ?array
    {
        $supplierProduct = $variant->supplierProduct;
        if (! $supplierProduct?->supplier) {
            return null;
        }

        try {
            $driver = Suppliers::supplier($supplierProduct->supplier);

            if (! $driver instanceof ProvidesUploadSpec) {
                return null;
            }

            $externalId = $supplierProduct->external_data['api_code']
                ?? $supplierProduct->external_id;

            $spec = $driver->resolveUploadSpec($externalId, $this->getApiSelections());

            return $spec?->toArray();
        } catch (\Exception $e) {
            logger()->warning('Failed to resolve upload spec for cart', [
                'error' => $e->getMessage(),
                'variant_id' => $variant->id,
            ]);

            return null;
        }
    }

    /**
     * Reset the configurator.
     */
    public function resetConfigurator(): void
    {
        $this->selections = [];
        $this->quantity = 1;
        $this->pricingData = null;
        $this->allKnownOptions = [];
        $this->initialize();
    }

    /**
     * Check if configuration can be ordered.
     */
    #[Computed]
    public function canOrder(): bool
    {
        return ($this->configurationData['can_order'] ?? false) && $this->pricingData !== null;
    }

    /**
     * Get available options.
     */
    #[Computed]
    public function availableOptions(): array
    {
        return $this->allKnownOptions;
    }

    /**
     * Get formatted cost price.
     */
    #[Computed]
    public function formattedCostPrice(): ?string
    {
        if (! $this->pricingData) {
            return null;
        }

        return number_format($this->pricingData['cost_price'] / 100, 2) . ' ' . $this->pricingData['currency'];
    }

    /**
     * Get formatted sell price.
     */
    #[Computed]
    public function formattedSellPrice(): ?string
    {
        if (! $this->pricingData) {
            return null;
        }

        return number_format($this->pricingData['sell_price'] / 100, 2, ',', '.');
    }

    /**
     * Get shipping price from breakdown.
     */
    #[Computed]
    public function shippingPrice(): ?float
    {
        return $this->pricingData['breakdown']['shipping_price'] ?? null;
    }

    /**
     * Get formatted shipping price.
     */
    #[Computed]
    public function formattedShippingPrice(): ?string
    {
        $price = $this->shippingPrice;
        if ($price === null) {
            return null;
        }

        return number_format($price, 2, ',', '.');
    }

    /**
     * Get delivery options with rush pricing.
     */
    #[Computed]
    public function deliveryOptions(): array
    {
        $options = $this->pricingData['all_price_options'] ?? [];
        if (empty($options)) {
            return [];
        }

        $margin = null;
        if ($this->productVariantId) {
            $variant = ProductVariant::find($this->productVariantId);
            if ($variant && $variant->isDynamic() && $variant->margin) {
                $margin = $variant->margin;
            }
        }

        return collect($options)->map(function ($option) use ($margin) {
            $productionHours = (int) ($option['production_hours'] ?? 96);
            $rushSurcharge = (float) ($option['products_purchase_rush_surcharge'] ?? 0);
            $costPrice = (float) ($option['products_purchase_price'] ?? 0);

            // Calculate sell price with margin if applicable
            $sellPrice = $margin
                ? $costPrice * (1 + ($margin / 100))
                : (float) ($option['products_sales_price'] ?? $costPrice);

            // Calculate estimated delivery date based on production hours + shipping time
            $deliveryDate = now()->addHours($productionHours + 24);

            return [
                'production_hours' => $productionHours,
                'delivery_date' => $deliveryDate,
                'formatted_date' => $deliveryDate->translatedFormat('l j M'),
                'rush_surcharge' => $rushSurcharge,
                'sell_price' => (int) round($sellPrice * 100),
                'is_express' => $productionHours < 48,
                'is_standard' => $productionHours >= 96,
            ];
        })->sortBy('production_hours')->values()->toArray();
    }

    /**
     * Get configuration summary for display.
     */
    #[Computed]
    public function configurationSummary(): array
    {
        return $this->getConfigurationDisplay();
    }

    /**
     * Get the supplier product.
     */
    #[Computed]
    public function supplierProduct(): ?SupplierProduct
    {
        if (! $this->supplierProductId) {
            return null;
        }

        return SupplierProduct::with('supplier')->find($this->supplierProductId);
    }

    /**
     * Update pricing based on current configuration.
     */
    protected function updatePricing(): void
    {
        if (! $this->supplierProductId) {
            return;
        }

        try {
            $service = $this->getService();
            $pricingSelections = [];
            $crossSellCodes = $this->getCrossSellCodes();

            foreach ($this->selections as $key => $value) {
                if ($this->shouldSkipForPricing($key, $crossSellCodes)) {
                    continue;
                }
                $pricingSelections[$key] = $value;
            }

            $service->setSelections($pricingSelections);
            $service->setQuantity($this->quantity);

            $priceResponse = $service->getPrice();

            // Apply margin if set on the variant (for dynamic products)
            $sellPrice = $priceResponse->sellPrice;
            if ($this->productVariantId) {
                $variant = ProductVariant::find($this->productVariantId);
                if ($variant && $variant->isDynamic() && $variant->margin) {
                    $sellPrice = (int) round($priceResponse->costPrice * (1 + ($variant->margin / 100)));
                }
            }

            $this->pricingData = [
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $sellPrice,
                'currency' => $priceResponse->currency,
                'breakdown' => $priceResponse->breakdown,
                'shipping_options' => $priceResponse->meta['shipping_options'] ?? [],
                'all_price_options' => $priceResponse->meta['all_price_options'] ?? [],
                'raw_response' => $priceResponse->meta['raw_response'] ?? [],
            ];
        } catch (\Exception) {
            $this->pricingData = null;
        }
    }

    /**
     * Process API response and update state.
     */
    private function processApiResponse($response): void
    {
        $this->configurationData = $response->toArray();

        $apiSelectedOptions = $response->selectedOptions;
        if (! empty($apiSelectedOptions)) {
            $apiSelectedOptions = array_filter(
                $apiSelectedOptions,
                fn ($key) => ! is_numeric($key),
                ARRAY_FILTER_USE_KEY
            );
            if (! empty($apiSelectedOptions)) {
                $this->selections = array_merge($this->selections, $apiSelectedOptions);
            }
        }

        $this->mergeNewOptions($response->availableOptions);
    }

    /**
     * Merge new options from API response.
     */
    protected function mergeNewOptions(array $newOptions): void
    {
        $existingCodes = array_column($this->allKnownOptions, 'code');

        foreach ($newOptions as $option) {
            $code = $option['code'] ?? null;
            if (! $code) {
                continue;
            }

            $existingIndex = array_search($code, $existingCodes);

            if ($existingIndex === false) {
                $this->allKnownOptions[] = $option;
                $existingCodes[] = $code;
            } else {
                $this->updateExistingOption($existingIndex, $option);
            }
        }
    }

    /**
     * Update an existing option with new data.
     */
    private function updateExistingOption(int $index, array $option): void
    {
        if (! empty($option['choices'])) {
            $this->allKnownOptions[$index]['choices'] = $option['choices'];
        }

        foreach (['min', 'max', 'step', 'default', 'description'] as $prop) {
            if (isset($option[$prop])) {
                $this->allKnownOptions[$index][$prop] = $option[$prop];
            }
        }
    }

    /**
     * Clear subsequent options after a change.
     */
    protected function clearSubsequentOptions(string $changedKey): void
    {
        $isDimensionChange = in_array($changedKey, self::DIMENSION_CODES);
        $optionIndex = $this->findOptionIndex($changedKey, $isDimensionChange);

        if ($optionIndex === null) {
            return;
        }

        $keysToRemove = $this->collectSubsequentKeys($optionIndex);
        foreach ($keysToRemove as $key) {
            unset($this->selections[$key]);
        }

        $this->allKnownOptions = array_slice($this->allKnownOptions, 0, $optionIndex + 1);
    }

    /**
     * Find option index by key.
     */
    private function findOptionIndex(string $changedKey, bool $isDimensionChange): ?int
    {
        $optionIndex = null;
        $lastDimensionIndex = null;

        // First pass: search by option code (group code for radio options)
        foreach ($this->allKnownOptions as $index => $option) {
            $code = $option['code'] ?? null;

            if (in_array($code, self::DIMENSION_CODES)) {
                $lastDimensionIndex = $index;
            }

            if ($code === $changedKey) {
                $optionIndex = $index;
                if (! $isDimensionChange) {
                    break;
                }
            }
        }

        // Second pass: only search choices if no direct code match found
        // This prevents finding "flag" in material choices when selecting composition "flag"
        if ($optionIndex === null) {
            foreach ($this->allKnownOptions as $index => $option) {
                if (($option['type'] ?? '') === 'select' && ! empty($option['choices'])) {
                    foreach ($option['choices'] as $choice) {
                        if (($choice['value'] ?? '') === $changedKey) {
                            return $isDimensionChange && $lastDimensionIndex !== null
                                ? $lastDimensionIndex
                                : $index;
                        }
                    }
                }
            }
        }

        return $isDimensionChange && $lastDimensionIndex !== null
            ? $lastDimensionIndex
            : $optionIndex;
    }

    /**
     * Collect all keys to remove after an option index.
     */
    private function collectSubsequentKeys(int $afterIndex): array
    {
        $keys = [];
        $optionCount = count($this->allKnownOptions);

        for ($i = $afterIndex + 1; $i < $optionCount; $i++) {
            $option = $this->allKnownOptions[$i];

            if (! empty($option['code'])) {
                $keys[] = $option['code'];
            }

            if (! empty($option['choices'])) {
                foreach ($option['choices'] as $choice) {
                    if (! empty($choice['value'])) {
                        $keys[] = $choice['value'];
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Check if both dimensions are set.
     */
    private function hasBothDimensions(): bool
    {
        return isset($this->selections['width']) && isset($this->selections['height']);
    }

    /**
     * Check if both dimensions have valid values.
     */
    private function hasBothValidDimensions(): bool
    {
        $width = $this->selections['width'] ?? null;
        $height = $this->selections['height'] ?? null;

        return ! empty($width) && $width > 0 && ! empty($height) && $height > 0;
    }

    /**
     * Clear cross-sell skip flag if selecting a cross-sell product.
     */
    private function clearCrossSellSkipFlag(string $key): void
    {
        foreach ($this->allKnownOptions as $option) {
            if (($option['type'] ?? '') === 'cross_sell'
                && ($option['code'] ?? '') === $key
                && isset($option['group_code'])) {
                unset($this->selections[$option['group_code'] . '_skip']);
                return;
            }
        }
    }

    /**
     * Find an option by code and type.
     */
    private function findOptionByCodeAndType(string $code, ?string $type = null): ?array
    {
        foreach ($this->allKnownOptions as $option) {
            $matchesCode = ($option['code'] ?? '') === $code;
            $matchesType = $type === null || ($option['type'] ?? '') === $type;

            if ($matchesCode && $matchesType) {
                return $option;
            }
        }
        return null;
    }

    /**
     * Get all cross-sell option codes.
     */
    private function getCrossSellCodes(): array
    {
        $codes = [];
        foreach ($this->allKnownOptions as $option) {
            if (($option['type'] ?? '') === 'cross_sell' && ! empty($option['code'])) {
                $codes[] = $option['code'];
            }
        }
        return $codes;
    }

    /**
     * Check if a selection key should be skipped for pricing.
     */
    private function shouldSkipForPricing(string $key, array $crossSellCodes): bool
    {
        return $key === 'quantity'
            || in_array($key, $crossSellCodes)
            || str_ends_with($key, '_skip');
    }

    /**
     * Get a human-readable version of the configuration for cart display.
     *
     * Uses parent option labels (group_label) and resolves choice labels
     * from allKnownOptions. Dimensions are combined into a single "Formaat" entry.
     */
    protected function getConfigurationDisplay(): array
    {
        $apiSelections = $this->getApiSelections();
        $optionsMap = collect($this->allKnownOptions)->keyBy('code');
        $dimensions = [];
        $display = [];
        $addedGroups = [];

        foreach ($apiSelections as $key => $value) {
            if ($this->isInternalKey($key)) {
                continue;
            }

            if ($this->isDimensionKey($key)) {
                $option = $optionsMap->get($key);
                $dimensions[$key] = ['value' => $value, 'unit' => $option['unit'] ?? 'cm'];
                continue;
            }

            $option = $this->findOptionForKey($key, $optionsMap);
            $label = $this->getOptionLabel($option, $key);

            if (isset($addedGroups[$label])) {
                continue;
            }
            $addedGroups[$label] = true;

            $display[$label] = $this->formatOptionValue($option, $key, $value);
        }

        return $this->prependDimensionsToDisplay($dimensions, $display);
    }

    /**
     * Check if the key is an internal key that should be skipped.
     */
    private function isInternalKey(string $key): bool
    {
        return in_array($key, ['hash', 'quantity', '_skip', 'amount']);
    }

    /**
     * Check if the key is a dimension key.
     */
    private function isDimensionKey(string $key): bool
    {
        return in_array($key, ['width', 'height', 'length']);
    }

    /**
     * Find the option definition for a key.
     */
    private function findOptionForKey(string $key, \Illuminate\Support\Collection $optionsMap): ?array
    {
        $option = $optionsMap->get($key);

        if ($option) {
            return $option;
        }

        // For radio/select options, the key might be a choice value
        return collect($this->allKnownOptions)
            ->first(fn ($opt) => collect($opt['choices'] ?? [])->contains('value', $key));
    }

    /**
     * Get the display label for an option.
     */
    private function getOptionLabel(?array $option, string $key): string
    {
        if (! $option) {
            return ucwords(str_replace(['_', '-'], ' ', $key));
        }

        return $option['group_label'] ?? $option['label'] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    /**
     * Format the display value for an option.
     */
    private function formatOptionValue(?array $option, string $key, mixed $value): string
    {
        if (! $option) {
            return (string) $value;
        }

        // For choice-based options, find the label
        if (! empty($option['choices'])) {
            $choice = collect($option['choices'])->firstWhere('value', $key)
                   ?? collect($option['choices'])->firstWhere('value', $value);

            return $choice['label'] ?? $value;
        }

        // For numeric values, format with unit or as number
        $displayValue = (string) $value;
        if (is_numeric($value)) {
            $displayValue = ! empty($option['unit'])
                ? number_format((float) $value, 1).' '.$option['unit']
                : number_format((float) $value, 0);
        }

        return $displayValue;
    }

    /**
     * Prepend formatted dimensions to the display array.
     */
    private function prependDimensionsToDisplay(array $dimensions, array $display): array
    {
        if (empty($dimensions)) {
            return $display;
        }

        $dimValues = [];
        $unit = 'cm';

        foreach (['width', 'height', 'length'] as $dim) {
            if (isset($dimensions[$dim])) {
                $dimValues[] = number_format((float) $dimensions[$dim]['value'], 1);
                $unit = $dimensions[$dim]['unit'];
            }
        }

        if (empty($dimValues)) {
            return $display;
        }

        return array_merge(['Formaat' => implode(' × ', $dimValues).' '.$unit], $display);
    }

    /**
     * Get selections filtered for API calls.
     * Removes internal keys like hash and _skip flags.
     * Unwraps numeric keys that were wrapped to prevent PHP integer conversion.
     */
    private function getApiSelections(): array
    {
        $filtered = array_filter($this->selections, function ($value, $key) {
            $unwrappedKey = $this->unwrapKey((string) $key);
            return $unwrappedKey !== 'hash'
                && $unwrappedKey !== 'quantity'
                && ! str_ends_with($unwrappedKey, '_skip');
        }, ARRAY_FILTER_USE_BOTH);

        // Unwrap keys for API
        $result = [];
        foreach ($filtered as $key => $value) {
            $unwrappedKey = $this->unwrapKey((string) $key);
            $result[$unwrappedKey] = $value;
        }

        return $result;
    }

    /**
     * Get the configurator service.
     */
    protected function getService(): ProboConfiguratorService
    {
        if (! $this->supplierProductId) {
            throw new \RuntimeException('No supplier product ID set');
        }

        if (! $this->service) {
            $this->service = ProboConfiguratorService::forSupplierProductId($this->supplierProductId);
        }

        return $this->service;
    }

    /**
     * Create a fresh service instance without caching.
     */
    protected function createFreshService(): ProboConfiguratorService
    {
        if (! $this->supplierProductId) {
            throw new \RuntimeException('No supplier product ID set');
        }

        return ProboConfiguratorService::forSupplierProductId($this->supplierProductId)
            ->withoutCache();
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.components.probo-configurator');
    }
}
