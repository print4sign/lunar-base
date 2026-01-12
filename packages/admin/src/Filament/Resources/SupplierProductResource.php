<?php

namespace Lunar\Admin\Filament\Resources;

use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\SupplierProductResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Contracts\SupplierProduct as SupplierProductContract;

class SupplierProductResource extends BaseResource
{
    protected static ?string $permission = 'settings:core';

    protected static ?string $model = SupplierProductContract::class;

    protected static ?int $navigationSort = 3;

    public static function getLabel(): string
    {
        return __('lunarpanel::supplierproduct.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::supplierproduct.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::supplier-products');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getNavigationBadge(): ?string
    {
        $discontinued = static::getModel()::discontinued()->count();

        return $discontinued > 0 ? (string) $discontinued : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters([
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->label(__('lunarpanel::supplierproduct.table.supplier.label'))
                    ->preload(),
                Tables\Filters\TernaryFilter::make('active')
                    ->label(__('lunarpanel::supplierproduct.table.active.label')),
                Tables\Filters\Filter::make('discontinued')
                    ->label(__('lunarpanel::supplierproduct.table.discontinued.label'))
                    ->query(fn (Builder $query): Builder => $query->discontinued()),
                Tables\Filters\Filter::make('has_replacement')
                    ->label(__('lunarpanel::supplierproduct.table.has_replacement.label'))
                    ->query(fn (Builder $query): Builder => $query->hasReplacement()),
                Tables\Filters\Filter::make('approaching_discontinuation')
                    ->label(__('lunarpanel::supplierproduct.table.approaching_discontinuation.label'))
                    ->query(fn (Builder $query): Builder => $query->approachingDiscontinuation(30)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('sync')
                    ->label(__('lunarpanel::supplierproduct.table.actions.sync.label'))
                    ->icon('lucide-refresh-cw')
                    ->action(function (Model $record) {
                        // Dispatch single product sync
                        $driver = \Lunar\Facades\Suppliers::supplier($record->supplier);
                        $productData = $driver->getProduct($record->external_id);

                        if ($productData) {
                            \Lunar\Jobs\Suppliers\CheckSupplierProductUpdate::dispatch($record, $productData);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('external_id')
                ->label(__('lunarpanel::supplierproduct.table.external_id.label'))
                ->searchable()
                ->sortable(),
            BadgeableColumn::make('external_name')
                ->separator('')
                ->suffixBadges([
                    Badge::make('active')
                        ->label(__('lunarpanel::supplierproduct.table.active.badge'))
                        ->color('success')
                        ->visible(fn (Model $record) => $record->active),
                    Badge::make('discontinued')
                        ->label(__('lunarpanel::supplierproduct.table.discontinued.badge'))
                        ->color('danger')
                        ->visible(fn (Model $record) => ! $record->active),
                    Badge::make('has_replacement')
                        ->label(__('lunarpanel::supplierproduct.table.has_replacement.badge'))
                        ->color('info')
                        ->visible(fn (Model $record) => $record->hasReplacement()),
                ])
                ->label(__('lunarpanel::supplierproduct.table.external_name.label'))
                ->searchable()
                ->limit(50),
            Tables\Columns\ImageColumn::make('supplier.icon')
                ->label('')
                ->circular()
                ->size(24)
                ->defaultImageUrl(fn () => 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>')),
            Tables\Columns\TextColumn::make('supplier.name')
                ->label(__('lunarpanel::supplierproduct.table.supplier.label'))
                ->sortable(),
            Tables\Columns\TextColumn::make('active_to')
                ->label(__('lunarpanel::supplierproduct.table.active_to.label'))
                ->date()
                ->sortable()
                ->placeholder('-'),
            Tables\Columns\TextColumn::make('replaced_by_external_id')
                ->label(__('lunarpanel::supplierproduct.table.replaced_by.label'))
                ->placeholder('-')
                ->url(fn (Model $record) => $record->replaced_by_id
                    ? static::getUrl('view', ['record' => $record->replaced_by_id])
                    : null)
                ->color('primary'),
            Tables\Columns\TextColumn::make('variants_count')
                ->counts('variants')
                ->label(__('lunarpanel::supplierproduct.table.variants_count.label')),
            Tables\Columns\TextColumn::make('last_synced_at')
                ->label(__('lunarpanel::supplierproduct.table.last_synced_at.label'))
                ->dateTime()
                ->sortable(),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('lunarpanel::supplierproduct.infolist.details.title'))
                    ->schema([
                        Infolists\Components\TextEntry::make('external_id')
                            ->label(__('lunarpanel::supplierproduct.infolist.external_id.label')),
                        Infolists\Components\TextEntry::make('external_name')
                            ->label(__('lunarpanel::supplierproduct.infolist.external_name.label')),
                        Infolists\Components\TextEntry::make('supplier.name')
                            ->label(__('lunarpanel::supplierproduct.infolist.supplier.label')),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make(__('lunarpanel::supplierproduct.infolist.lifecycle.title'))
                    ->schema([
                        Infolists\Components\IconEntry::make('active')
                            ->label(__('lunarpanel::supplierproduct.infolist.active.label'))
                            ->boolean(),
                        Infolists\Components\TextEntry::make('active_to')
                            ->label(__('lunarpanel::supplierproduct.infolist.active_to.label'))
                            ->date()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('replaced_by_external_id')
                            ->label(__('lunarpanel::supplierproduct.infolist.replaced_by_external_id.label'))
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('replacedBy.external_name')
                            ->label(__('lunarpanel::supplierproduct.infolist.replaced_by_name.label'))
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make(__('lunarpanel::supplierproduct.infolist.linked_variants.title'))
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('variants')
                            ->schema([
                                Infolists\Components\TextEntry::make('sku')
                                    ->label(__('lunarpanel::supplierproduct.infolist.variant_sku.label'))
                                    ->url(fn ($record) => ProductVariantResource::getUrl('edit', ['record' => $record]))
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label(__('lunarpanel::supplierproduct.infolist.product_name.label'))
                                    ->getStateUsing(fn ($record) => $record->product?->translateAttribute('name'))
                                    ->url(fn ($record) => ProductResource::getUrl('edit', ['record' => $record->product]))
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('purchasable')
                                    ->label(__('lunarpanel::supplierproduct.infolist.purchasable.label'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'always' => 'success',
                                        'in_stock' => 'info',
                                        'backorder' => 'warning',
                                        'discontinued' => 'danger',
                                        default => 'gray',
                                    }),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make(__('lunarpanel::supplierproduct.infolist.sync_info.title'))
                    ->schema([
                        Infolists\Components\IconEntry::make('synced')
                            ->label(__('lunarpanel::supplierproduct.infolist.synced.label'))
                            ->boolean(),
                        Infolists\Components\TextEntry::make('last_synced_at')
                            ->label(__('lunarpanel::supplierproduct.infolist.last_synced_at.label'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('lunarpanel::supplierproduct.infolist.created_at.label'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('lunarpanel::supplierproduct.infolist.updated_at.label'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListSupplierProducts::route('/'),
            'view' => Pages\ViewSupplierProduct::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['supplier', 'replacedBy']);
    }
}
