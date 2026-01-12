<?php

namespace Lunar\Admin\Filament\Resources\ProductVariantResource\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Admin\Filament\Resources\SupplierResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Facades\Suppliers;
use Lunar\Models\Currency;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;

class ManageVariantFulfillment extends BaseEditRecord
{
    protected static string $resource = ProductVariantResource::class;

    protected static string $view = 'lunarpanel::filament.resources.product-variant-resource.pages.manage-variant-fulfillment';

    public function getTitle(): string
    {
        return __('lunarpanel::productvariant.pages.fulfillment.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::productvariant.pages.fulfillment.label');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::fulfillment') ?? 'heroicon-o-truck';
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $hasSupplier = $record->supplier_product_id !== null;

        $actions = [
            ProductVariantResource::getVariantSwitcherWidget($record),
        ];

        if ($hasSupplier) {
            $isDynamic = $record->isDynamic();

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
                            ->default($record->margin)
                            ->helperText(__('lunarpanel::productvariant.fulfillment.margin_help')),
                    ])
                    ->action(function (array $data) {
                        $this->getRecord()->update(['margin' => $data['margin']]);

                        Notification::make()
                            ->title(__('lunarpanel::productvariant.fulfillment.notifications.margin_updated'))
                            ->success()
                            ->send();

                        $this->refreshFormData(['*']);
                    });
            }

            $actions[] = Action::make('configure')
                ->label(__('lunarpanel::productvariant.fulfillment.actions.configure'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('primary')
                ->action(function () {
                    $this->dispatch(
                        'configure-variant-with-probo',
                        variantId: $this->getRecord()->id,
                        supplierProductId: $this->getRecord()->supplier_product_id,
                        productId: $this->getRecord()->product_id
                    );
                });

            $actions[] = Action::make('refresh_price')
                ->label(__('lunarpanel::productvariant.fulfillment.actions.refresh_price'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshSupplierPrice();
                });

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
                    $this->dispatch(
                        'open-supplier-configurator',
                        supplierProductId: $data['supplier_product_id'],
                        variantId: $this->getRecord()->id,
                        productId: $this->getRecord()->product_id
                    );
                });
        }

        return $actions;
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->url(function (Model $record) {
            return ProductResource::getUrl('variants', [
                'record' => $record->product,
            ]);
        });
    }

    public function getBreadcrumbs(): array
    {
        return [
            ...ProductVariantResource::getBaseBreadcrumbs(
                $this->getRecord()
            ),
            ProductVariantResource::getUrl('fulfillment', [
                'record' => $this->getRecord(),
            ]) => $this->getTitle(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getRecord())
            ->schema([
                // Dynamic product info banner
                Infolists\Components\Section::make(__('lunarpanel::productvariant.fulfillment.dynamic.title'))
                    ->schema([
                        Infolists\Components\TextEntry::make('dynamic_info')
                            ->label('')
                            ->state(__('lunarpanel::productvariant.fulfillment.dynamic.info'))
                            ->icon('heroicon-o-bolt')
                            ->iconColor('warning'),
                        Infolists\Components\TextEntry::make('margin_display')
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
        $record = $this->getRecord();

        if (! $record->supplierProduct || ! $record->configuration) {
            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.notifications.no_configuration'))
                ->warning()
                ->send();
            return;
        }

        $supplier = $record->supplierProduct->supplier;

        if (! $supplier->supports('pricing')) {
            Notification::make()
                ->title(__('lunarpanel::productvariant.fulfillment.notifications.pricing_not_supported'))
                ->warning()
                ->send();
            return;
        }

        try {
            $priceResponse = Suppliers::supplier($supplier)
                ->getPrice($record->supplierProduct->external_id, $record->configuration);

            $currency = Currency::getDefault();

            $record->prices()->updateOrCreate(
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

            $this->refreshFormData(['*']);
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
        $record = $this->getRecord();

        $record->update([
            'supplier_product_id' => null,
            'configuration' => null,
        ]);

        $record->prices()
            ->whereNotNull('supplier_id')
            ->delete();

        Notification::make()
            ->title(__('lunarpanel::productvariant.fulfillment.notifications.unlinked'))
            ->success()
            ->send();

        $this->refreshFormData(['*']);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
