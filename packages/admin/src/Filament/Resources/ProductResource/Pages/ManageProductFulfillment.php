<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\SupplierResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Facades\Suppliers;
use Lunar\Models\Contracts\ProductVariant as ProductVariantContract;
use Lunar\Models\Currency;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class ManageProductFulfillment extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    protected static string $view = 'lunarpanel::filament.resources.product-resource.pages.manage-product-fulfillment';

    public function getTitle(): string|Htmlable
    {
        return __('lunarpanel::product.pages.fulfillment.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::product.pages.fulfillment.label');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return $parameters['record']->variants()->withTrashed()->count() == 1;
    }

    public function getBreadcrumb(): string
    {
        return __('lunarpanel::product.pages.fulfillment.label');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::fulfillment') ?? 'heroicon-o-truck';
    }

    protected function getVariant(): ProductVariantContract
    {
        return $this->getRecord()->variants()->withTrashed()->first();
    }

    protected function getHeaderActions(): array
    {
        $variant = $this->getVariant();
        $hasSupplier = $variant->supplier_product_id !== null;

        $actions = [];

        if ($hasSupplier) {
            // Dynamic toggle action
            $isDynamic = $variant->isDynamic();

            // Margin action - only for dynamic variants
            if ($isDynamic) {
                $actions[] = Action::make('set_margin')
                    ->label(__('lunarpanel::productvariant.fulfillment.actions.set_margin'))
                    ->icon('heroicon-o-percent-badge')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('margin')
                            ->label(__('lunarpanel::productvariant.fulfillment.margin_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->default($variant->margin)
                            ->helperText(__('lunarpanel::productvariant.fulfillment.margin_help')),
                    ])
                    ->action(function (array $data) {
                        $this->getVariant()->update(['margin' => $data['margin']]);

                        Notification::make()
                            ->title(__('lunarpanel::productvariant.fulfillment.notifications.margin_updated'))
                            ->success()
                            ->send();

                        $this->redirect(ProductResource::getUrl('fulfillment', ['record' => $this->getRecord()]));
                    });
            }

            $actions[] = Action::make('toggle_dynamic')
                ->label($isDynamic
                    ? __('lunarpanel::productvariant.fulfillment.actions.disable_dynamic')
                    : __('lunarpanel::productvariant.fulfillment.actions.enable_dynamic')
                )
                ->icon($isDynamic ? 'heroicon-o-bolt-slash' : 'heroicon-o-bolt')
                ->color($isDynamic ? 'warning' : 'success')
                ->requiresConfirmation()
                ->modalHeading($isDynamic
                    ? __('lunarpanel::productvariant.fulfillment.dynamic.disable_title')
                    : __('lunarpanel::productvariant.fulfillment.dynamic.enable_title')
                )
                ->modalDescription($isDynamic
                    ? __('lunarpanel::productvariant.fulfillment.dynamic.disable_description')
                    : __('lunarpanel::productvariant.fulfillment.dynamic.enable_description')
                )
                ->action(function () {
                    $this->toggleDynamic();
                });

            // Only show configure action for non-dynamic variants
            if (! $isDynamic) {
                $actions[] = Action::make('configure')
                    ->label(__('lunarpanel::productvariant.fulfillment.actions.configure'))
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->action(function () {
                        $variant = $this->getVariant();
                        $this->dispatch(
                            'configure-variant-with-probo',
                            variantId: $variant->id,
                            supplierProductId: $variant->supplier_product_id,
                            productId: $variant->product_id
                        );
                    });

                $actions[] = Action::make('refresh_price')
                    ->label(__('lunarpanel::productvariant.fulfillment.actions.refresh_price'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function () {
                        $this->refreshSupplierPrice();
                    });
            }

            $actions[] = Action::make('unlink')
                ->label(__('lunarpanel::productvariant.fulfillment.actions.unlink'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->unlinkSupplier();
                });
        } else {
            $actions[] = Action::make('link_supplier')
                ->label(__('lunarpanel::productvariant.fulfillment.actions.link_supplier'))
                ->icon('heroicon-o-link')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('supplier_id')
                        ->label(__('lunarpanel::productvariant.fulfillment.supplier'))
                        ->options(
                            Supplier::enabled()->orderBy('name')->pluck('name', 'id')
                        )
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('supplier_product_id', null)),
                    Forms\Components\Select::make('supplier_product_id')
                        ->label(__('lunarpanel::productvariant.fulfillment.supplier_product'))
                        ->options(function (Forms\Get $get) {
                            $supplierId = $get('supplier_id');
                            if (! $supplierId) {
                                return [];
                            }

                            return SupplierProduct::where('supplier_id', $supplierId)
                                ->orderBy('external_name')
                                ->get()
                                ->mapWithKeys(fn ($product) => [
                                    $product->id => $product->external_name ?: $product->external_id,
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->visible(fn (Forms\Get $get) => filled($get('supplier_id'))),
                ])
                ->action(function (array $data) {
                    $variant = $this->getVariant();

                    // Use JavaScript to dispatch the event after the action modal closes
                    // This prevents the "Could not find Livewire component in DOM tree" error
                    $this->js(<<<JS
                        setTimeout(() => {
                            Livewire.dispatch('configure-variant-with-probo', {
                                variantId: {$variant->id},
                                supplierProductId: {$data['supplier_product_id']},
                                productId: {$variant->product_id}
                            });
                            window.dispatchEvent(new CustomEvent('open-modal', {
                                detail: { id: 'probo-configurator-modal' }
                            }));
                        }, 100);
                    JS);
                });
        }

        return $actions;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $variant = $this->getVariant();

        return $infolist
            ->record($variant)
            ->schema([
                // Dynamic product info banner
                Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.dynamic.title'))
                    ->schema([
                        Infolists\Components\TextEntry::make('dynamic_info')
                            ->label('')
                            ->state(__('lunarpanel::productvariant.fulfillment.dynamic.info'))
                            ->icon('heroicon-o-bolt')
                            ->iconColor('warning'),
                        Infolists\Components\TextEntry::make('margin')
                            ->label(__('lunarpanel::productvariant.fulfillment.margin'))
                            ->state(fn ($record) => $record->margin !== null
                                ? number_format($record->margin, 2).'%'
                                : __('lunarpanel::productvariant.fulfillment.no_margin_set')
                            )
                            ->icon('heroicon-o-percent-badge')
                            ->iconColor(fn ($record) => $record->margin !== null ? 'success' : 'gray'),
                    ])
                    ->visible(fn ($record) => $record->isDynamic())
                    ->extraAttributes(['class' => 'bg-warning-50 dark:bg-warning-950']),

                Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.supplier'))
                    ->schema([
                        Infolists\Components\TextEntry::make('supplierProduct.supplier.name')
                            ->label(__('lunarpanel::productvariant.fulfillment.supplier'))
                            ->url(fn ($record) => $record->supplierProduct?->supplier
                                ? SupplierResource::getUrl('edit', ['record' => $record->supplierProduct->supplier])
                                : null
                            ),
                        Infolists\Components\TextEntry::make('supplierProduct.external_name')
                            ->label(__('lunarpanel::productvariant.fulfillment.supplier_product')),
                        Infolists\Components\TextEntry::make('supplierProduct.external_id')
                            ->label(__('lunarpanel::productvariant.fulfillment.external_id'))
                            ->copyable(),
                        Infolists\Components\TextEntry::make('supplierProduct.supplier.driver')
                            ->label(__('lunarpanel::productvariant.fulfillment.driver'))
                            ->badge(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->supplier_product_id !== null),

                Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.pricing'))
                    ->schema([
                        Infolists\Components\TextEntry::make('cost_price')
                            ->label(__('lunarpanel::productvariant.fulfillment.cost_price'))
                            ->state(function ($record) {
                                $price = $record->prices()->whereNotNull('supplier_id')->first()
                                    ?? $record->prices()->first();
                                return $price?->cost_price?->formatted ?? '—';
                            }),
                        Infolists\Components\TextEntry::make('sell_price')
                            ->label(__('lunarpanel::productvariant.fulfillment.sell_price'))
                            ->state(function ($record) {
                                $price = $record->prices()->whereNotNull('supplier_id')->first()
                                    ?? $record->prices()->first();
                                return $price?->price?->formatted ?? '—';
                            }),
                        Infolists\Components\TextEntry::make('margin')
                            ->label(__('lunarpanel::productvariant.fulfillment.margin'))
                            ->state(function ($record) {
                                $price = $record->prices()->whereNotNull('supplier_id')->first()
                                    ?? $record->prices()->first();

                                if (! $price || ! $price->cost_price || ! $price->price) {
                                    return '—';
                                }

                                $costValue = $price->cost_price->value;
                                $sellValue = $price->price->value;

                                if ($sellValue <= 0) {
                                    return '—';
                                }

                                $margin = (($sellValue - $costValue) / $sellValue) * 100;
                                return number_format($margin, 1) . '%';
                            })
                            ->color(fn ($state) => $state !== '—' && floatval($state) > 20 ? 'success' : 'warning'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->supplier_product_id !== null),

                Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.configuration'))
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('configuration')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->supplier_product_id !== null && ! empty($record->configuration))
                    ->collapsible(),

                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('no_supplier')
                            ->label('')
                            ->state(__('lunarpanel::productvariant.fulfillment.no_supplier'))
                            ->icon('heroicon-o-information-circle')
                            ->iconColor('warning'),
                    ])
                    ->visible(fn ($record) => $record->supplier_product_id === null),
            ]);
    }

    protected function refreshSupplierPrice(): void
    {
        $variant = $this->getVariant();

        if (! $variant->supplierProduct || ! $variant->configuration) {
            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.notifications.no_configuration'))
                ->warning()
                ->send();
            return;
        }

        $supplier = $variant->supplierProduct->supplier;

        if (! $supplier->supports('pricing')) {
            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.notifications.pricing_not_supported'))
                ->warning()
                ->send();
            return;
        }

        try {
            $priceResponse = Suppliers::supplier($supplier)
                ->getPrice($variant->supplierProduct->external_id, $variant->configuration);

            $currency = Currency::getDefault();

            $variant->prices()->updateOrCreate(
                [
                    'currency_id' => $currency->id,
                    'customer_group_id' => null,
                    'min_quantity' => 1,
                ],
                [
                    'price' => (int) ($priceResponse->sellPrice * $currency->factor),
                    'cost_price' => (int) ($priceResponse->costPrice * $currency->factor),
                    'supplier_id' => $supplier->id,
                ]
            );

            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.notifications.price_refreshed'))
                ->success()
                ->send();

            $this->redirect(ProductResource::getUrl('fulfillment', ['record' => $this->getRecord()]));
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.notifications.refresh_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function unlinkSupplier(): void
    {
        $variant = $this->getVariant();

        $variant->update([
            'supplier_product_id' => null,
            'configuration' => null,
            'is_dynamic' => false,
        ]);

        $variant->prices()
            ->whereNotNull('supplier_id')
            ->delete();

        Notification::make()
            ->title(__('lunarpanel::productvariant.fulfillment.notifications.unlinked'))
            ->success()
            ->send();

        $this->redirect(ProductResource::getUrl('fulfillment', ['record' => $this->getRecord()]));
    }

    protected function toggleDynamic(): void
    {
        $variant = $this->getVariant();

        $newState = ! $variant->is_dynamic;

        $variant->update([
            'is_dynamic' => $newState,
        ]);

        // If enabling dynamic mode, clear any pre-configured configuration and prices
        if ($newState) {
            $variant->update([
                'configuration' => null,
            ]);

            $variant->prices()
                ->whereNotNull('supplier_id')
                ->delete();
        }

        Notification::make()
            ->title(__('lunarpanel::productvariant.fulfillment.notifications.dynamic_toggled'))
            ->body($newState
                ? __('lunarpanel::productvariant.fulfillment.notifications.dynamic_enabled')
                : __('lunarpanel::productvariant.fulfillment.notifications.dynamic_disabled')
            )
            ->success()
            ->send();

        $this->redirect(ProductResource::getUrl('fulfillment', ['record' => $this->getRecord()]));
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
