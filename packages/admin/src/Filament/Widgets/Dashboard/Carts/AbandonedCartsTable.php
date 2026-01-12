<?php

namespace Lunar\Admin\Filament\Widgets\Dashboard\Carts;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Models\Cart;

class AbandonedCartsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected function getTablePollingInterval(): ?string
    {
        return '60s';
    }

    public static function getHeading(): ?string
    {
        return __('lunarpanel::widgets.dashboard.carts.abandoned_carts.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Cart::query()
                    ->with(['user', 'currency', 'lines.purchasable.product'])
                    ->whereNull('deleted_at')
                    ->whereHas('lines')
                    ->where('updated_at', '<', now()->subHours(24))
                    ->whereDoesntHave('orders')
                    ->orderBy('updated_at', 'desc')
                    ->limit(10);
            })
            ->columns([
                TextColumn::make('id')
                    ->label(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.columns.id'))
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.columns.customer'))
                    ->default(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.guest'))
                    ->searchable(),

                TextColumn::make('user.email')
                    ->label(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.columns.email'))
                    ->default('-')
                    ->searchable(),

                TextColumn::make('lines_count')
                    ->label(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.columns.items'))
                    ->counts('lines')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.columns.abandoned_at'))
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('created_at')
                    ->label(__('lunarpanel::widgets.dashboard.carts.abandoned_carts.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->paginated(false)
            ->searchable(false)
            ->heading($this->getHeading());
    }
}
