<?php

namespace Lunar\Admin\Livewire\Components;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;
use Lunar\Models\SupplierProduct;
use Lunar\Services\ProboConfiguratorService;

class ProboConfigurator extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    /**
     * The supplier product ID being configured.
     */
    #[Locked]
    public ?int $supplierProductId = null;

    /**
     * The product ID to create variants for.
     */
    #[Locked]
    public ?int $productId = null;

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
     * Saving state for the link variant action.
     */
    public bool $isSaving = false;

    /**
     * Current configuration response.
     */
    public ?array $configurationData = null;

    /**
     * All known options from initialization (used to preserve dimension inputs).
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
     * Target variant ID to link the configuration to.
     * This is set when the configurator is opened from a specific variant row.
     */
    #[Locked]
    public ?int $targetVariantId = null;

    /**
     * The configurator service instance.
     */
    protected ?ProboConfiguratorService $service = null;

    /**
     * Mount the component.
     */
    public function mount(?int $supplierProductId = null, ?int $productId = null, ?int $targetVariantId = null): void
    {
        $this->supplierProductId = $supplierProductId;
        $this->productId = $productId;
        $this->targetVariantId = $targetVariantId;

        if ($this->supplierProductId) {
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

            $this->updatePricing();
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Dimension codes that should be treated as a group.
     */
    private const DIMENSION_CODES = ['width', 'height', 'length'];

    /**
     * Handle option selection.
     */
    public function selectOption(string $key, mixed $value): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        $isDimensionChange = in_array($key, self::DIMENSION_CODES);

        try {
            // For dimension changes, only clear if we already have both width and height set
            if (! $isDimensionChange || $this->hasBothDimensions()) {
                $this->clearSubsequentOptions($key);
            }

            $this->selections[$key] = $value;

            // Clear cross-sell skip flag if selecting a cross-sell product
            $this->clearCrossSellSkipFlag($key);

            // Wait for both dimensions before calling API
            if ($isDimensionChange && ! $this->hasBothValidDimensions()) {
                $this->isLoading = false;
                return;
            }

            $response = $this->getService()->configure($this->selections);
            $this->processApiResponse($response);
            $this->updatePricing();

            // Always dispatch scroll event after successful selection
            $this->dispatch('scroll-to-next-option');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Check if both width and height are set.
     */
    private function hasBothDimensions(): bool
    {
        return isset($this->selections['width']) && isset($this->selections['height']);
    }

    /**
     * Check if both dimensions have valid (non-zero) values.
     */
    private function hasBothValidDimensions(): bool
    {
        $width = $this->selections['width'] ?? null;
        $height = $this->selections['height'] ?? null;

        return ! empty($width) && $width > 0 && ! empty($height) && $height > 0;
    }

    /**
     * Clear cross-sell skip flag if the given key is a cross-sell product.
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
     * Process API response and update state.
     */
    private function processApiResponse($response): void
    {
        $this->configurationData = $response->toArray();

        // Merge API selections (only associative keys to avoid corruption)
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
     * Handle radio option selection (only one choice can be selected per group).
     * Removes other choices from the same group before selecting the new one.
     */
    public function selectRadioOption(string $groupCode, string $selectedValue, array $allChoiceValues): void
    {
        // Remove any previously selected choice from this group
        foreach ($allChoiceValues as $choiceValue) {
            unset($this->selections[$choiceValue]);
        }

        // Now select the new option using the standard selectOption method
        $this->selectOption($selectedValue, $selectedValue);
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
    }

    /**
     * Select a cross-sell product with a specific amount.
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

        $this->updatePricing();
    }

    /**
     * Find an option by code and optionally by type.
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
     * Clear options that come after the given option in the configuration flow.
     * Dimension options (width, height, length) are treated as a group.
     */
    protected function clearSubsequentOptions(string $changedKey): void
    {
        $isDimensionChange = in_array($changedKey, self::DIMENSION_CODES);
        $optionIndex = $this->findOptionIndex($changedKey, $isDimensionChange);

        if ($optionIndex === null) {
            return;
        }

        // Collect and remove subsequent options
        $keysToRemove = $this->collectSubsequentKeys($optionIndex);
        foreach ($keysToRemove as $key) {
            unset($this->selections[$key]);
        }

        $this->allKnownOptions = array_slice($this->allKnownOptions, 0, $optionIndex + 1);
    }

    /**
     * Find the index of an option by its key.
     */
    private function findOptionIndex(string $changedKey, bool $isDimensionChange): ?int
    {
        $optionIndex = null;
        $lastDimensionIndex = null;

        foreach ($this->allKnownOptions as $index => $option) {
            $code = $option['code'] ?? null;

            if (in_array($code, self::DIMENSION_CODES)) {
                $lastDimensionIndex = $index;
            }

            // Check select option choices
            if (($option['type'] ?? '') === 'select' && ! empty($option['choices'])) {
                foreach ($option['choices'] as $choice) {
                    if (($choice['value'] ?? '') === $changedKey) {
                        return $isDimensionChange && $lastDimensionIndex !== null
                            ? $lastDimensionIndex
                            : $index;
                    }
                }
            }

            if ($code === $changedKey) {
                $optionIndex = $index;
                if (! $isDimensionChange) {
                    break;
                }
            }
        }

        return $isDimensionChange && $lastDimensionIndex !== null
            ? $lastDimensionIndex
            : $optionIndex;
    }

    /**
     * Collect all keys that should be removed after a given option index.
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
     * Select both width and height dimensions at once (for preset sizes).
     */
    public function selectDimensions(float $width, float $height): void
    {
        $this->isLoading = true;
        $this->errorMessage = null;

        try {
            $this->clearSubsequentOptions('height');
            $this->selections['width'] = $width;
            $this->selections['height'] = $height;

            $response = $this->getService()->configure($this->selections);
            $this->processApiResponse($response);
            $this->updatePricing();

            // Dispatch scroll event after successful dimension selection
            $this->dispatch('scroll-to-next-option');
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Update quantity.
     */
    public function updatedQuantity(): void
    {
        $this->quantity = max(1, $this->quantity);
        $this->selections['quantity'] = $this->quantity;

        // Only update pricing if we have a supplier product selected
        if ($this->supplierProductId) {
            $this->updatePricing();
        }
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
            $service->setQuantity($this->quantity);

            $crossSellCodes = $this->getCrossSellCodes();

            foreach ($this->selections as $key => $value) {
                if ($this->shouldSkipForPricing($key, $crossSellCodes)) {
                    continue;
                }
                $service->setOption($key, $value);
            }

            $priceResponse = $service->getPrice();
            $this->pricingData = [
                'cost_price' => $priceResponse->costPrice,
                'sell_price' => $priceResponse->sellPrice,
                'currency' => $priceResponse->currency,
                'breakdown' => $priceResponse->breakdown,
                'shipping_options' => $priceResponse->meta['shipping_options'] ?? [],
            ];
            $this->dispatchPricingUpdate();
        } catch (\Exception) {
            // Pricing might fail if configuration is incomplete
            $this->pricingData = null;
            $this->dispatchPricingUpdate();
        }
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
     * Dispatch pricing update event to the modal footer.
     */
    protected function dispatchPricingUpdate(): void
    {
        $this->dispatch('configurator-pricing-updated', [
            'costPrice' => $this->formattedCostPrice,
            'sellPrice' => $this->formattedSellPrice,
            'selectionsCount' => count(array_filter($this->selections)),
        ]);
    }

    /**
     * Reset the configurator.
     */
    #[On('configurator-reset')]
    public function resetConfigurator(): void
    {
        $this->selections = [];
        $this->quantity = 1;
        $this->pricingData = null;
        $this->allKnownOptions = [];
        $this->initialize();
        $this->dispatchPricingUpdate();
    }

    /**
     * Unlink configuration and supplier product from the target variant.
     */
    #[On('configurator-unlink')]
    public function unlinkConfiguration(): void
    {
        if (! $this->targetVariantId) {
            return;
        }

        $variant = ProductVariant::find($this->targetVariantId);
        if (! $variant) {
            $this->notifyError('variant_not_found');
            return;
        }

        // Check if variant has anything to unlink
        if (empty($variant->configuration) && empty($variant->supplier_product_id)) {
            Notification::make()
                ->title(__('lunarpanel::product.configurator.notifications.no_configuration'))
                ->warning()
                ->send();
            return;
        }

        $variant->update([
            'configuration' => null,
            'supplier_product_id' => null,
        ]);

        // Remove supplier-related pricing
        $variant->prices()
            ->whereNotNull('supplier_id')
            ->delete();

        Notification::make()
            ->title(__('lunarpanel::product.configurator.notifications.unlinked'))
            ->success()
            ->send();

        $this->dispatch('variant-updated', variantId: $variant->id);
        $this->dispatch('configurator-saved');
    }

    /**
     * Check if the current variant has a configuration or supplier product that can be unlinked.
     */
    #[Computed]
    public function hasConfiguration(): bool
    {
        if (! $this->targetVariantId) {
            return false;
        }

        $variant = ProductVariant::find($this->targetVariantId);
        return $variant && (! empty($variant->configuration) || ! empty($variant->supplier_product_id));
    }

    /**
     * Save configuration (triggered from modal footer).
     */
    #[On('configurator-save')]
    public function saveConfiguration(): void
    {
        $this->linkVariant();
    }

    /**
     * Link configuration to the target variant.
     */
    public function linkVariantAction(): Action
    {
        return Action::make('linkVariant')
            ->label(__('lunarpanel::product.configurator.actions.link_variant'))
            ->disabled(fn () => ! $this->canOrder() || ! $this->targetVariantId)
            ->action(function () {
                $this->linkVariant();
            });
    }

    /**
     * Link the configuration to the target variant.
     */
    public function linkVariant(): void
    {
        if (! $this->canOrder()) {
            $this->notifyError('incomplete');
            return;
        }

        if (! $this->targetVariantId) {
            $this->notifyError('select_variant');
            return;
        }

        $this->isSaving = true;

        try {
            $variant = ProductVariant::find($this->targetVariantId);
            if (! $variant) {
                $this->notifyError('variant_not_found');
                return;
            }

            $supplierProduct = SupplierProduct::find($this->supplierProductId);
            $service = $this->getService();
            $service->setSelections($this->selections);
            $variantData = $service->toVariantData();

            $this->updateVariantWithSupplierData($variant, $supplierProduct, $variantData);

            Notification::make()
                ->title(__('lunarpanel::product.configurator.notifications.variant_linked'))
                ->success()
                ->send();

            $this->dispatch('variant-updated', variantId: $variant->id);
            $this->dispatch('configurator-saved');
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('lunarpanel::product.configurator.notifications.error'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSaving = false;
        }
    }

    /**
     * Send an error notification.
     */
    private function notifyError(string $key): void
    {
        Notification::make()
            ->title(__("lunarpanel::product.configurator.notifications.{$key}"))
            ->danger()
            ->send();
    }

    /**
     * Update variant with supplier configuration and pricing.
     */
    private function updateVariantWithSupplierData(
        ProductVariant $variant,
        SupplierProduct $supplierProduct,
        array $variantData
    ): void {
        $variant->update([
            'supplier_product_id' => $this->supplierProductId,
            'configuration' => $variantData['configuration'],
        ]);

        $currency = Currency::where('code', 'EUR')->first()
            ?? Currency::whereDefault(true)->first()
            ?? Currency::first();

        $priceData = [
            'currency_id' => $currency->id,
            'price' => $variantData['base_price'] ?: $variantData['cost_price'],
            'cost_price' => $variantData['cost_price'],
            'supplier_id' => $supplierProduct->supplier_id,
            'min_quantity' => 1,
        ];

        $variant->prices()->updateOrCreate(
            [
                'currency_id' => $currency->id,
                'customer_group_id' => null,
                'min_quantity' => 1,
            ],
            $priceData
        );
    }

    /**
     * Check if the configuration can be ordered.
     */
    #[Computed]
    public function canOrder(): bool
    {
        return ($this->configurationData['can_order'] ?? false) && $this->pricingData !== null;
    }

    /**
     * Merge new options from API response with all known options.
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

        unset($this->availableOptions);
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
     * Get available options from configuration.
     * Uses allKnownOptions to preserve dimension inputs that may not be returned in every API response.
     */
    #[Computed]
    public function availableOptions(): array
    {
        return $this->allKnownOptions;
    }

    /**
     * Get the next step label.
     */
    #[Computed]
    public function nextStep(): ?string
    {
        return $this->configurationData['next_step'] ?? null;
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

        return number_format($this->pricingData['cost_price'] / 100, 2).' '.$this->pricingData['currency'];
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

        return number_format($this->pricingData['sell_price'] / 100, 2).' '.$this->pricingData['currency'];
    }

    /**
     * Get the footer pricing HTML for injection into modal footer.
     */
    public function getFooterPricingHtml(): string
    {
        $selectionsCount = count(array_filter($this->selections));
        $costPrice = $this->formattedCostPrice;
        $sellPrice = $this->formattedSellPrice;

        $costLabel = __('lunarpanel::product.configurator.cost_price');
        $sellLabel = __('lunarpanel::product.configurator.sell_price');
        $completeLabel = __('lunarpanel::product.configurator.complete_config_for_price');
        $selectionsLabel = __('lunarpanel::product.configurator.selections_made');

        $priceHtml = $sellPrice
            ? '<div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 dark:text-gray-400">'.e($costLabel).':</span>
                    <span class="font-medium text-gray-900 dark:text-white">'.e($costPrice).'</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-medium text-gray-900 dark:text-white">'.e($sellLabel).':</span>
                    <span class="text-lg font-bold text-primary-600 dark:text-primary-400">'.e($sellPrice).'</span>
                </div>
               </div>'
            : '<span class="text-gray-500 dark:text-gray-400">'.e($completeLabel).'</span>';

        $selectionsHtml = $selectionsCount > 0
            ? '<span class="text-gray-500 dark:text-gray-400">'.$selectionsCount.' '.e($selectionsLabel).'</span>'
            : '';

        return '<div class="flex items-center justify-between gap-4 text-sm">
                    <div class="flex items-center gap-6">'.$priceHtml.'</div>
                    '.$selectionsHtml.'
                </div>';
    }

    /**
     * Get the configurator service.
     *
     * @throws \RuntimeException if no supplier product ID is set
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
     * Listen for supplier product selection.
     */
    #[On('supplier-product-selected')]
    public function onSupplierProductSelected(int $supplierProductId, ?int $productId = null): void
    {
        $this->supplierProductId = $supplierProductId;
        if ($productId) {
            $this->productId = $productId;
        }
        $this->service = null;
        $this->initialize();
    }

    /**
     * Open the configurator for a specific variant.
     */
    #[On('configure-variant-with-probo')]
    public function onConfigureVariantWithProbo(int $variantId, int $supplierProductId, ?int $productId = null): void
    {
        $this->targetVariantId = $variantId;
        $this->supplierProductId = $supplierProductId;
        if ($productId) {
            $this->productId = $productId;
        }
        $this->resetState();
        $this->initialize();
    }

    /**
     * Reset component state.
     */
    private function resetState(): void
    {
        $this->service = null;
        $this->selections = [];
        $this->pricingData = null;
        $this->allKnownOptions = [];
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('lunarpanel::livewire.components.probo-configurator');
    }
}
