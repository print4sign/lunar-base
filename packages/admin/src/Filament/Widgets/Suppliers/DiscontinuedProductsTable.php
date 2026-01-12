<?php

namespace Lunar\Admin\Filament\Widgets\Suppliers;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Admin\Filament\Resources\SupplierProductResource;
use Lunar\Models\SupplierProduct;

class DiscontinuedProductsTable extends BaseWidget
{
    protected static ?string $heading = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public function getHeading(): ?string
    {
        return __('lunarpanel::widgets.suppliers.discontinued_products.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SupplierProduct::query()
                    ->with(['supplier', 'replacedBy'])
                    ->where(function (Builder $query) {
                        $query->discontinued()
                            ->orWhere(function (Builder $q) {
                                $q->approachingDiscontinuation(30);
                            });
                    })
                    ->orderByRaw('CASE WHEN active = 0 THEN 0 ELSE 1 END')
                    ->orderBy('active_to')
            )
            ->columns([
                Tables\Columns\TextColumn::make('external_id')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.external_id'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_name')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.name'))
                    ->limit(40),
                Tables\Columns\ImageColumn::make('supplier.icon')
                    ->label('')
                    ->circular()
                    ->size(24)
                    ->defaultImageUrl(fn () => 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>')),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.supplier')),
                Tables\Columns\IconColumn::make('active')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.status'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('active_to')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.active_to'))
                    ->date()
                    ->placeholder('-')
                    ->color(fn (SupplierProduct $record) => $record->isApproachingDiscontinuation() ? 'warning' : null),
                Tables\Columns\TextColumn::make('replaced_by_external_id')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.replacement'))
                    ->placeholder(__('lunarpanel::widgets.suppliers.discontinued_products.no_replacement')),
                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.variants')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.view'))
                    ->icon('heroicon-m-eye')
                    ->url(fn (SupplierProduct $record): string => SupplierProductResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('migrate')
                    ->label(__('lunarpanel::widgets.suppliers.discontinued_products.migrate'))
                    ->icon('heroicon-m-arrow-right')
                    ->visible(fn (SupplierProduct $record): bool => $record->hasReplacement())
                    ->requiresConfirmation()
                    ->action(function (SupplierProduct $record) {
                        // Migrate variants to replacement
                        $record->variants()->update([
                            'supplier_product_id' => $record->replaced_by_id,
                        ]);

                        $this->notify('success', __('lunarpanel::widgets.suppliers.discontinued_products.migrated'));
                    }),
            ])
            ->emptyStateHeading(__('lunarpanel::widgets.suppliers.discontinued_products.empty'))
            ->emptyStateDescription(__('lunarpanel::widgets.suppliers.discontinued_products.empty_description'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated([5, 10, 25]);
    }
}
