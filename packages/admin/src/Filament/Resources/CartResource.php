<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Admin\Filament\Resources\CartResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Contracts\Cart as CartContract;

class CartResource extends BaseResource
{
    protected static ?string $permission = 'sales:manage-orders';

    protected static ?string $model = CartContract::class;

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return __('lunarpanel::cart.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::cart.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('heroicon-o-shopping-cart') ?? 'heroicon-o-shopping-cart';
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('lunarpanel::order.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.sales');
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->with(['user', 'currency', 'lines'])
                    ->whereNull('deleted_at')
                    ->whereHas('lines')
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->recordUrl(fn ($record) => Pages\ViewCart::getUrl(['record' => $record]))
            ->defaultSort('updated_at', 'DESC')
            ->deferLoading()
            ->poll('30s');
    }

    public static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('lunarpanel::cart.table.id.label'))
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->label(__('lunarpanel::cart.table.status.label'))
                ->badge()
                ->getStateUsing(function ($record) {
                    if ($record->completedOrders()->exists()) {
                        return __('lunarpanel::cart.status.completed');
                    }

                    $minutesAgo = $record->updated_at->diffInMinutes(now());

                    if ($minutesAgo <= 15) {
                        return __('lunarpanel::cart.status.active');
                    } elseif ($minutesAgo <= 60) {
                        return __('lunarpanel::cart.status.idle');
                    } elseif ($minutesAgo <= 1440) {
                        return __('lunarpanel::cart.status.inactive');
                    }

                    return __('lunarpanel::cart.status.abandoned');
                })
                ->color(fn (string $state): string => match ($state) {
                    __('lunarpanel::cart.status.completed') => 'success',
                    __('lunarpanel::cart.status.active') => 'info',
                    __('lunarpanel::cart.status.idle') => 'warning',
                    __('lunarpanel::cart.status.inactive') => 'gray',
                    default => 'danger',
                }),

            Tables\Columns\TextColumn::make('user.name')
                ->label(__('lunarpanel::cart.table.customer.label'))
                ->default(__('lunarpanel::cart.guest'))
                ->searchable(),

            Tables\Columns\TextColumn::make('user.email')
                ->label(__('lunarpanel::cart.table.email.label'))
                ->default('-')
                ->searchable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('lines_count')
                ->label(__('lunarpanel::cart.table.items.label'))
                ->counts('lines')
                ->sortable(),

            Tables\Columns\TextColumn::make('lines_quantity')
                ->label(__('lunarpanel::cart.table.quantity.label'))
                ->getStateUsing(fn ($record) => $record->lines->sum('quantity')),

            Tables\Columns\TextColumn::make('created_at')
                ->label(__('lunarpanel::cart.table.created_at.label'))
                ->dateTime()
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->label(__('lunarpanel::cart.table.updated_at.label'))
                ->dateTime()
                ->sortable()
                ->since(),
        ];
    }

    public static function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->label(__('lunarpanel::cart.table.status.label'))
                ->options([
                    'active' => __('lunarpanel::cart.status.active'),
                    'idle' => __('lunarpanel::cart.status.idle'),
                    'inactive' => __('lunarpanel::cart.status.inactive'),
                    'abandoned' => __('lunarpanel::cart.status.abandoned'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (empty($data['value'])) {
                        return $query;
                    }

                    $now = now();

                    return match ($data['value']) {
                        'active' => $query->where('updated_at', '>=', $now->copy()->subMinutes(15)),
                        'idle' => $query->where('updated_at', '>=', $now->copy()->subMinutes(60))
                            ->where('updated_at', '<', $now->copy()->subMinutes(15)),
                        'inactive' => $query->where('updated_at', '>=', $now->copy()->subHours(24))
                            ->where('updated_at', '<', $now->copy()->subMinutes(60)),
                        'abandoned' => $query->where('updated_at', '<', $now->copy()->subHours(24)),
                        default => $query,
                    };
                }),

            Tables\Filters\TernaryFilter::make('has_user')
                ->label(__('lunarpanel::cart.filter.has_user.label'))
                ->placeholder(__('lunarpanel::cart.filter.has_user.all'))
                ->trueLabel(__('lunarpanel::cart.filter.has_user.registered'))
                ->falseLabel(__('lunarpanel::cart.filter.has_user.guest'))
                ->queries(
                    true: fn (Builder $query) => $query->whereNotNull('user_id'),
                    false: fn (Builder $query) => $query->whereNull('user_id'),
                ),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListCarts::route('/'),
            'view' => Pages\ViewCart::route('/{record}'),
        ];
    }
}
