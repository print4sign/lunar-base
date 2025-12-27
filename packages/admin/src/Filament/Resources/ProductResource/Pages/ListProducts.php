<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Pages\BaseListRecords;
use Lunar\Facades\DB;
use Lunar\Facades\Suppliers;
use Lunar\Models\Attribute;
use Lunar\Models\Currency;
use Lunar\Models\Product;
use Lunar\Models\Supplier;
use Lunar\Models\SupplierProduct;
use Lunar\Models\TaxClass;

class ListProducts extends BaseListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\CreateAction::make('create_manual')
                    ->label(__('lunarpanel::product.actions.create_manual.label'))
                    ->createAnother(false)
                    ->form(fn () => static::createActionFormInputs())
                    ->using(fn (array $data, string $model) => static::createRecord($data, $model))
                    ->successRedirectUrl(fn (Model $record): string => ProductResource::getUrl('edit', [
                        'record' => $record,
                    ])),
                Actions\CreateAction::make('create_from_supplier')
                    ->label(__('lunarpanel::product.actions.create_from_supplier.label'))
                    ->createAnother(false)
                    ->form(fn () => static::createFromSupplierFormInputs())
                    ->using(fn (array $data, string $model) => static::createFromSupplierRecord($data, $model))
                    ->successRedirectUrl(fn (Model $record): string => ProductResource::getUrl('edit', [
                        'record' => $record,
                    ]))
                    ->visible(fn () => Supplier::enabled()->exists()),
            ])
                ->label(__('lunarpanel::product.actions.create.label'))
                ->button()
                ->icon('lucide-plus'),
        ];
    }

    public static function createActionFormInputs(): array
    {
        return [
            Grid::make(2)->schema([
                ProductResource::getBaseNameFormComponent(),
                ProductResource::getProductTypeFormComponent()->required(),
            ]),
            Grid::make(2)->schema([
                ProductResource::getSkuFormComponent(),
                ProductResource::getBasePriceFormComponent(),
            ]),
        ];
    }

    public static function createRecord(array $data, string $model): Model
    {
        $currency = Currency::getDefault();

        $nameAttribute = Attribute::whereAttributeType(
            $model::morphName()
        )
            ->whereHandle('name')
            ->first()
            ->type;

        DB::beginTransaction();
        $product = $model::create([
            'status' => 'draft',
            'product_type_id' => $data['product_type_id'],
            'attribute_data' => [
                'name' => new $nameAttribute($data['name']),
            ],
        ]);
        $variant = $product->variants()->create([
            'tax_class_id' => TaxClass::getDefault()->id,
            'sku' => $data['sku'],
        ]);
        $variant->prices()->create([
            'min_quantity' => 1,
            'currency_id' => $currency->id,
            'price' => (int) bcmul($data['base_price'], $currency->factor),
        ]);
        DB::commit();

        return $product;
    }

    public static function createFromSupplierFormInputs(): array
    {
        return [
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
            Grid::make(2)->schema([
                ProductResource::getBaseNameFormComponent(),
                ProductResource::getProductTypeFormComponent()->required(),
            ]),
        ];
    }

    public static function createFromSupplierRecord(array $data, string $model): Model
    {
        $currency = Currency::getDefault();
        $supplierProduct = SupplierProduct::find($data['supplier_product_id']);

        $nameAttribute = Attribute::whereAttributeType(
            $model::morphName()
        )
            ->whereHandle('name')
            ->first()
            ->type;

        DB::beginTransaction();

        $product = $model::create([
            'status' => 'draft',
            'product_type_id' => $data['product_type_id'],
            'attribute_data' => [
                'name' => new $nameAttribute($data['name']),
            ],
        ]);

        $variant = $product->variants()->create([
            'tax_class_id' => TaxClass::getDefault()->id,
            'supplier_product_id' => $supplierProduct->id,
            'sku' => $supplierProduct->external_id,
        ]);

        // Fetch pricing from supplier if available
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

        // Link supplier product to product
        $supplierProduct->update(['product_id' => $product->id]);

        DB::commit();

        return $product;
    }

    public function getDefaultTabs(): array
    {
        return [
            'all' => Tab::make(__('lunarpanel::product.tabs.all')),
            'published' => Tab::make(__('lunarpanel::product.tabs.published'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'draft' => Tab::make(__('lunarpanel::product.tabs.draft'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(Product::query()->where('status', 'draft')->count()),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}
