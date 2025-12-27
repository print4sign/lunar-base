<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Pages\BaseManageRelatedRecords;
use Lunar\Facades\Suppliers;
use Lunar\Models\Currency;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Models\ProductVariant;
use Lunar\Models\TaxClass;

class ManageProductVariants extends BaseManageRelatedRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $relationship = 'variants';

    protected static string $view = 'lunarpanel::filament.resources.product-resource.pages.manage-product-variants';

    protected function getDefaultHeaderWidgets(): array
    {
        return [
            ProductResource\Widgets\ProductOptionsWidget::class,
        ];
    }

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\Action::make('add_supplier_variant')
                ->label(__('lunarpanel::product.actions.add_supplier_variant.label'))
                ->icon('lucide-factory')
                ->form([
                    Forms\Components\Select::make('supplier_id')
                        ->label(__('lunarpanel::product.form.supplier_id.label'))
                        ->options(fn () => Supplier::enabled()->pluck('name', 'id'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('supplier_product_id', null)),
                    Forms\Components\Select::make('supplier_product_id')
                        ->label(__('lunarpanel::product.form.supplier_product_id.label'))
                        ->options(fn (Forms\Get $get) => SupplierProduct::where('supplier_id', $get('supplier_id'))
                            ->pluck('external_name', 'id'))
                        ->required()
                        ->searchable()
                        ->visible(fn (Forms\Get $get) => filled($get('supplier_id'))),
                    Forms\Components\TextInput::make('sku')
                        ->label(__('lunarpanel::product.form.sku.label'))
                        ->helperText(__('lunarpanel::product.actions.add_supplier_variant.sku_helper'))
                        ->placeholder(__('lunarpanel::product.actions.add_supplier_variant.sku_placeholder')),
                ])
                ->action(function (array $data) {
                    $currency = Currency::getDefault();
                    $supplierProduct = SupplierProduct::find($data['supplier_product_id']);
                    $product = $this->getOwnerRecord();

                    $variant = $product->variants()->create([
                        'tax_class_id' => TaxClass::getDefault()->id,
                        'supplier_product_id' => $supplierProduct->id,
                        'sku' => $data['sku'] ?: $supplierProduct->external_id,
                    ]);

                    $priceData = [
                        'min_quantity' => 1,
                        'currency_id' => $currency->id,
                        'price' => 0,
                        'supplier_id' => $supplierProduct->supplier_id,
                    ];

                    if ($supplierProduct->supplier->supports('pricing')) {
                        try {
                            $priceResponse = Suppliers::supplier($supplierProduct->supplier)
                                ->getPrice($supplierProduct->external_id, []);

                            $priceData['price'] = (int) ($priceResponse->sellPrice * $currency->factor);
                            $priceData['cost_price'] = (int) ($priceResponse->costPrice * $currency->factor);
                        } catch (\Exception $e) {
                            // Use default pricing if supplier API fails
                        }
                    }

                    $variant->prices()->create($priceData);

                    // Link supplier product to variant
                    $supplierProduct->update(['product_variant_id' => $variant->id]);

                    Notification::make()
                        ->title(__('lunarpanel::product.actions.add_supplier_variant.success'))
                        ->success()
                        ->send();
                })
                ->visible(fn () => Supplier::enabled()->exists()),
        ];
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::product-variants');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return config('lunar.panel.enable_variants', true);
    }

    public static function canAccess(array $parameters = []): bool
    {
        if (! config('lunar.panel.enable_variants', true)) {
            return false;
        }

        return parent::canAccess($parameters);
    }

    public function getTitle(): string
    {
        return __('lunarpanel::product.pages.variants.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::product.pages.variants.label');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table;

        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('sku'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                //                Tables\Actions\AssociateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DissociateAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //                    Tables\Actions\DissociateBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
