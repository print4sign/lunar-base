<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Widgets;

use Awcodes\Shout\Components\Shout;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Lunar\Admin\Actions\Products\MapVariantsToProductOptions;
use Lunar\Admin\Events\ProductVariantOptionsUpdated;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Facades\DB;
use Lunar\Models\Contracts\ProductOption as ProductOptionContract;
use Lunar\Models\Contracts\ProductOptionValue as ProductOptionValueContract;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductVariant;
use Livewire\Attributes\On;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class ProductOptionsWidget extends BaseWidget implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'lunarpanel::resources.product-resource.widgets.product-options';

    public ?Model $record;

    public array $variants = [];

    /**
     * The product options which are being actively configured.
     */
    public array $configuredOptions = [];

    public bool $configuringOptions = false;

    protected static bool $isLazy = false;

    public function mount()
    {
        $this->configureBaseOptions();
    }

    /**
     * Refresh the widget when a variant is updated from the configurator.
     */
    #[On('variant-updated')]
    public function refreshWidget(): void
    {
        $this->configureBaseOptions();
    }

    public function addSharedOptionAction()
    {
        $existing = collect($this->configuredOptions)->pluck('id');
        $options = ProductOption::whereNotIn('id', $existing)
            ->shared()
            ->get();

        return Action::make('addSharedOption')
            ->form([
                Shout::make('no_shared_components')
                    ->content(
                        __('lunarpanel::productoption.widgets.product-options.actions.add-shared-option.form.no_shared_components.label')
                    )
                    ->visible(
                        $options->isEmpty()
                    ),
                Select::make('product_option')
                    ->options(
                        fn () => $options->mapWithKeys(
                            fn ($option) => [$option->id => $option->translate('name')]
                        )
                    )->label(
                        __('lunarpanel::productoption.widgets.product-options.actions.add-shared-option.form.product_option.label')
                    )->visible(
                        $options->isNotEmpty()
                    ),
                Toggle::make('preselect')
                    ->default(true)
                    ->label(
                        __('lunarpanel::productoption.widgets.product-options.actions.add-shared-option.form.preselect.label')
                    )->visible(
                        $options->isNotEmpty()
                    ),
            ])->action(function (array $data) {
                $productOption = ProductOption::with(['values'])->find($data['product_option']);
                $this->configuredOptions[] = $this->mapOption(
                    $productOption,
                    $productOption->values->map(
                        fn ($value) => $this->mapOptionValue($value, $data['preselect'] ?? false)
                    )->toArray()
                );
            })->after(
                fn () => ProductVariantOptionsUpdated::dispatch($this->record)
            );
    }

    public function configureBaseOptions(): void
    {
        $productOptions = $this->query()->get();

        $sharedOptionIds = $productOptions->filter(
            fn ($option) => $option->shared
        )->pluck('id');

        $disabledSharedOptionValues = ProductOptionValue::whereIn(
            'product_option_id',
            $sharedOptionIds
        )->whereNotIn(
            'id',
            $productOptions->pluck('values')->flatten()->pluck('id')
        )->get();

        $options = [];

        foreach ($productOptions as $productOption) {
            $values = $productOption->values->count() ? $productOption->values->map(function ($value) {
                return $this->mapOptionValue($value, true);
            })->merge(
                $disabledSharedOptionValues->filter(
                    fn ($value) => $value->product_option_id == $productOption->id
                )->map(
                    fn ($value) => $this->mapOptionValue($value, false)
                )
            )->sortBy('position')->values()->toArray() : [];

            $options[] = $this->mapOption($productOption, $values);
        }

        $this->configuredOptions = $options;

        $this->mapVariantPermutations(fillMissing: false);
    }

    public function cancelOptionConfiguring(): void
    {
        $this->configuringOptions = false;
        $this->configureBaseOptions();
    }

    public function query()
    {
        return $this->record->productOptions()
            ->with('values', function ($query) {
                $query->whereHas('variants', function ($relation) {
                    $relation->whereIn($relation->getModel()->getTable().'.id', $this->record->variants()->pluck('id'));
                });
            });
    }

    public function addRestrictedOption()
    {
        $this->configuredOptions[] = [
            'id' => null,
            'value' => '',
            'position' => count($this->configuredOptions) + 1,
            'readonly' => false,
            'option_values' => [
                [
                    'id' => null,
                    'value' => '',
                    'position' => 1,
                    'enabled' => true,
                ],
            ],
        ];
    }

    public function updateConfiguredOptions()
    {
        $this->validate([
            'configuredOptions' => 'array',
            'configuredOptions.*.value' => 'required|string',
            'configuredOptions.*.option_values.*.value' => 'required|string',
        ]);

        // Go through each one and if a configuration has none enabled, then just
        // remove it from the array.
        $options = collect();

        foreach ($this->configuredOptions as $configuredOption) {
            $enabledCount = collect($configuredOption['option_values'])
                ->filter(
                    fn ($value) => $value['enabled']
                )->count();

            if ($enabledCount) {
                $options->push($configuredOption);
            }
        }

        $this->configuredOptions = $options->values()->toArray();

        $this->mapVariantPermutations();

        $this->configuringOptions = false;
    }

    public function removeVariant($key): void
    {
        unset($this->variants[$key]);
    }

    public function addOptionValue($path)
    {
        $option = $this->configuredOptions[$path];

        if ($option['readonly']) {
            return;
        }

        $this->configuredOptions[$path]['option_values'][] = [
            'value' => '',
            'position' => count($this->configuredOptions[$path]['option_values']) + 1,
            'readonly' => false,
            'enabled' => true,
        ];
    }

    public function removeOptionValue($index, $valueIndex)
    {
        unset($this->configuredOptions[$index]['option_values'][$valueIndex]);
    }

    public function removeOption($index)
    {
        $options = collect($this->configuredOptions)->forget($index);
        $this->configuredOptions = $options->values()->toArray();
    }

    public function updateValuePositions($optionKey, $rows)
    {
        $this->configuredOptions[$optionKey]['option_values'] = $rows;
    }

    public function updateOptionPositions($rows)
    {
        $this->configuredOptions = $rows;
    }

    public function mapVariantPermutations($fillMissing = true): void
    {
        $optionValues = collect($this->configuredOptions)
            ->filter(
                fn ($option) => $option['value']
            )
            ->mapWithKeys(
                fn ($option) => [$option['value'] => collect($option['option_values'])
                    ->filter(
                        fn ($value) => $value['enabled']
                    )
                    ->map(
                        fn ($value) => $value['value']
                    )]
            )->toArray();

        $variants = $this->record->variants->load(['basePrices.currency', 'basePrices.priceable', 'values.option'])->map(function ($variant) {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->basePrices->first()?->price->decimal ?: 0,
                'stock' => $variant->stock,
                'values' => $variant->values->mapWithKeys(
                    fn ($value) => [$value->option->translate('name') => $value->translate('name')]
                )->toArray(),
            ];
        })->toArray();

        $this->variants = MapVariantsToProductOptions::map($optionValues, $variants, $fillMissing);
    }

    public function getHasNewVariantsProperty()
    {
        return collect($this->variants)
            ->reject(
                fn ($variant) => $variant['variant_id']
            )->isNotEmpty();
    }

    protected function storeConfiguredOptions(): void
    {
        $language = Language::getDefault();
        /**
         * Go through our configured options and if they don't
         * exist in the database i.e. they are new, create and map them
         * so they are ready.
         */
        foreach ($this->configuredOptions as $optionIndex => $option) {

            $optionModel = empty($option['id']) ?
                new ProductOption([
                    'shared' => false,
                ]) :
                ProductOption::find($option['id']);

            $optionValue = $option['value'];

            if (! $optionModel->shared) {
                $optionModel->name = [
                    $language->code => $optionValue,
                ];
                $optionModel->label = [
                    $language->code => $optionValue,
                ];
                $optionModel->handle = Str::slug($optionValue);
                $optionModel->save();
            }

            if ($optionModel->shared) {
                continue;
            }

            $this->configuredOptions[$optionIndex]['id'] = $optionModel->id;
            $option['id'] = $optionModel->id;

            foreach ($option['option_values'] as $optionValueIndex => $value) {
                $optionValueModel = empty($value['id']) ?
                    new ProductOptionValue([
                        'product_option_id' => $option['id'],
                    ]) :
                    ProductOptionValue::find($value['id']);

                $optionValueModel->name = [
                    $language->code => $value['value'],
                ];
                $optionValueModel->position = $value['position'];
                $optionValueModel->save();

                $this->configuredOptions[$optionIndex]['option_values'][$optionValueIndex]['id'] =
                    $optionValueModel->id;
            }
        }
    }

    protected function mapOptionValuesToIds(array $values): array
    {
        $valueIds = [];
        foreach ($values as $option => $value) {
            $configuredOption = collect(
                $this->configuredOptions
            )->first(
                fn ($o) => $o['value'] == $option
            );

            $valueId = collect($configuredOption['option_values'])->first(
                fn ($v) => $v['value'] == $value
            )['id'];
            $valueIds[] = $valueId;
        }

        return $valueIds;
    }

    public function saveVariantsAction()
    {
        return Action::make('saveVariants')
            ->action(function () {
                DB::beginTransaction();

                $this->storeConfiguredOptions();

                /**
                 * If there are no variants, then all the configured option
                 * have been removed. In this case we still want to keep a
                 * variant at least one is needed for Lunar to function.
                 */
                if (! count($this->variants)) {
                    $variant = $this->record->variants()->first();
                    $variant->values()->detach();
                    $this->record->productOptions()->exclusive()->each(
                        fn (ProductOptionContract $productOption) => $productOption->delete()
                    );

                    $this->record->productOptions()->shared()->detach();
                    $this->record->variants()
                        ->where('id', '!=', $variant->id)
                        ->get()
                        ->each(
                            fn (ProductVariantContract $variant) => $variant->delete()
                        );

                    DB::commit();

                    Notification::make()->title(
                        __('lunarpanel::productoption.widgets.product-options.notifications.save-variants.success.title')
                    )->success()->send();

                    return;
                }

                foreach ($this->variants as $variantIndex => $variantData) {
                    $variant = new ProductVariant([
                        'product_id' => $this->record->id,
                    ]);
                    $basePrice = null;
                    $currency = Currency::getDefault();

                    if (! empty($variantData['variant_id'])) {
                        $variant = ProductVariant::find($variantData['variant_id']);
                        $basePrice = $variant->basePrices->first();
                    }

                    if (! empty($variantData['copied_id'])) {
                        $copiedVariant = ProductVariant::find(
                            $variantData['copied_id']
                        );

                        $variant = $copiedVariant->replicate();
                        $variant->save();

                        $basePrice = $copiedVariant->basePrices->first()?->replicate();
                        if ($basePrice) {
                            $basePrice->priceable_id = $variant->id;
                        }
                    }

                    $variant->sku = $variantData['sku'];
                    $variant->stock = $variantData['stock'];
                    $variant->save();

                    // Create a new base price if one doesn't exist
                    if (! $basePrice) {
                        $basePrice = $variant->prices()->create([
                            'min_quantity' => 1,
                            'currency_id' => $currency->id,
                            'price' => (int) bcmul($variantData['price'], $currency->factor),
                        ]);
                    } else {
                        $priceCurrency = $basePrice->currency ?? $currency;
                        $basePrice->price = (int) bcmul($variantData['price'], $priceCurrency->factor);
                        $basePrice->save();
                    }

                    $optionsValues = $this->mapOptionValuesToIds($variantData['values']);

                    $variant->values()->sync($optionsValues);

                    $this->variants[$variantIndex]['variant_id'] = $variant->id;
                }

                $productOptions = collect($this->configuredOptions)
                    ->mapWithKeys(function ($option) {
                        return [
                            $option['id'] => [
                                'position' => $option['position'],
                            ],
                        ];
                    });

                $this->record->productOptions()->sync($productOptions);

                $variantIds = collect($this->variants)->pluck('variant_id');

                $this->record->variants()->whereNotIn('id', $variantIds)
                    ->get()
                    ->each(
                        fn ($variant) => $variant->delete()
                    );
                DB::commit();

                Notification::make()->title(
                    __('lunarpanel::productoption.widgets.product-options.notifications.save-variants.success.title')
                )->success()->send();
            })->after(
                fn () => ProductVariantOptionsUpdated::dispatch($this->record)
            );
    }

    public function getVariantLink($variantId)
    {
        return ProductVariantResource::getUrl('edit', [
            'product' => $this->record,
            'record' => $variantId,
        ]);
    }

    /**
     * Open the configurator slideOver directly for a variant.
     */
    public function openConfiguratorSlideOver(int $supplierProductId, ?int $variantId, int $productId): void
    {
        $this->mountAction('supplierConfigurator', [
            'supplierProductId' => $supplierProductId,
            'variantId' => $variantId,
            'productId' => $productId,
        ]);
    }

    /**
     * Open the supplier selection modal for a variant without a supplier product.
     */
    public function openSupplierSelectionModal(?int $variantId, int $productId): void
    {
        $this->mountAction('selectSupplier', [
            'variantId' => $variantId,
            'productId' => $productId,
        ]);
    }

    /**
     * Action to select a supplier and product before opening the configurator.
     */
    public function selectSupplierAction(): Action
    {
        return Action::make('selectSupplier')
            ->modalHeading(__('lunarpanel::product.configurator.modal.title'))
            ->form([
                Select::make('supplier_id')
                    ->label(__('lunarpanel::product.configurator.modal.select_supplier'))
                    ->options(fn () => Supplier::query()->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (callable $set) => $set('supplier_product_id', null)),
                Select::make('supplier_product_id')
                    ->label(__('lunarpanel::product.configurator.modal.select_product'))
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search, callable $get) {
                        $supplierId = $get('supplier_id');
                        if (! $supplierId) {
                            return [];
                        }

                        return SupplierProduct::where('supplier_id', $supplierId)
                            ->where(function ($query) use ($search) {
                                $query->where('external_name', 'like', "%{$search}%")
                                    ->orWhere('external_id', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->pluck('external_name', 'id');
                    })
                    ->getOptionLabelUsing(fn ($value) => SupplierProduct::find($value)?->external_name)
                    ->required()
                    ->visible(fn (callable $get) => filled($get('supplier_id'))),
            ])
            ->action(function (array $data, array $arguments) {
                // Open the configurator with the selected supplier product
                $this->mountAction('supplierConfigurator', [
                    'supplierProductId' => $data['supplier_product_id'],
                    'variantId' => $arguments['variantId'] ?? null,
                    'productId' => $arguments['productId'] ?? $this->record->id,
                ]);
            });
    }

    /**
     * Action to display the supplier configurator slideOver.
     */
    public function supplierConfiguratorAction(): Action
    {
        return Action::make('supplierConfigurator')
            ->slideOver()
            ->stickyModalFooter()
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalHeading(fn (array $arguments) => $this->getConfiguratorHeading($arguments))
            ->modalWidth('4xl')
            ->modalContent(fn (array $arguments) => view('lunarpanel::filament.modals.supplier-configurator', [
                'variantId' => $arguments['variantId'] ?? null,
                'supplierProductId' => $arguments['supplierProductId'] ?? null,
                'productId' => $arguments['productId'] ?? $this->record->id,
                'driver' => $this->getSupplierDriver($arguments['supplierProductId'] ?? null),
            ]))
            ->modalFooterActions(fn (array $arguments) => [
                Action::make('saveConfiguration')
                    ->label(__('lunarpanel::product.configurator.actions.link_variant'))
                    ->alpineClickHandler('$dispatch("configurator-save")'),
                Action::make('unlinkConfiguration')
                    ->label(__('lunarpanel::product.configurator.actions.unlink'))
                    ->color('danger')
                    ->alpineClickHandler('$dispatch("configurator-unlink")'),
                Action::make('resetConfigurator')
                    ->label(__('lunarpanel::product.configurator.actions.reset'))
                    ->color('gray')
                    ->alpineClickHandler('$dispatch("configurator-reset")'),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('filament::components/modal.actions.close.label'));
    }

    /**
     * Get the configurator modal heading with product name.
     */
    protected function getConfiguratorHeading(array $arguments): string
    {
        $supplierProductId = $arguments['supplierProductId'] ?? null;

        if (! $supplierProductId) {
            return __('lunarpanel::product.configurator.modal.title');
        }

        $supplierProduct = SupplierProduct::find($supplierProductId);
        $productName = $supplierProduct?->external_name;

        if ($productName) {
            return __('lunarpanel::product.configurator.modal.title_with_product', ['product' => $productName]);
        }

        return __('lunarpanel::product.configurator.modal.title');
    }

    /**
     * Get the supplier driver for a supplier product.
     */
    protected function getSupplierDriver(?int $supplierProductId): ?string
    {
        if (! $supplierProductId) {
            return null;
        }

        $supplierProduct = SupplierProduct::with('supplier')->find($supplierProductId);

        return $supplierProduct?->supplier?->driver;
    }

    protected function mapOptionValue(ProductOptionValueContract $value, bool $enabled = true)
    {
        /** @var ProductOptionValue $value */
        return [
            'id' => $value->id,
            'enabled' => $enabled,
            'value' => $value->translate('name'),
            'position' => $value->position,
        ];
    }

    protected function mapOption(ProductOptionContract $option, array $values = []): array
    {
        /** @var ProductOption $option */
        return [
            'id' => $option->id,
            'key' => "option_{$option->id}",
            'value' => $option->translate('name'),
            'position' => $option->pivot?->position ?: count($this->configuredOptions) + 1,
            'readonly' => $option->shared,
            'option_values' => $values,
        ];
    }
}
