<?php

namespace Lunar\Admin\Filament\Widgets\Dashboard\Carts;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Lunar\Models\Cart;

class ActiveCartsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '15s';

    protected function getTablePollingInterval(): ?string
    {
        return '15s';
    }

    public static function getHeading(): ?string
    {
        return __('lunarpanel::widgets.dashboard.carts.active_carts.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Cart::query()
                    ->with(['user', 'currency', 'lines.purchasable.product'])
                    ->whereNull('deleted_at')
                    ->whereHas('lines')
                    ->where(function ($query) {
                        $query->whereDoesntHave('orders')
                            ->orWhereHas('orders', fn ($q) => $q->whereNull('placed_at'));
                    })
                    ->orderBy('updated_at', 'desc')
                    ->limit(15);
            })
            ->columns([
                TextColumn::make('id')
                    ->label(__('lunarpanel::widgets.dashboard.carts.active_carts.columns.id'))
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('lunarpanel::widgets.dashboard.carts.active_carts.columns.customer'))
                    ->default(__('lunarpanel::widgets.dashboard.carts.active_carts.guest'))
                    ->searchable(),

                TextColumn::make('lines_count')
                    ->label(__('lunarpanel::widgets.dashboard.carts.active_carts.columns.items'))
                    ->counts('lines')
                    ->sortable(),

                TextColumn::make('lines_sum')
                    ->label(__('lunarpanel::widgets.dashboard.carts.active_carts.columns.quantity'))
                    ->getStateUsing(fn (Cart $record) => $record->lines->sum('quantity')),

                TextColumn::make('updated_at')
                    ->label(__('lunarpanel::widgets.dashboard.carts.active_carts.columns.last_activity'))
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('status')
                    ->label(__('lunarpanel::widgets.dashboard.carts.active_carts.columns.status'))
                    ->badge()
                    ->getStateUsing(function (Cart $record) {
                        $minutesAgo = $record->updated_at->diffInMinutes(now());

                        if ($minutesAgo <= 15) {
                            return __('lunarpanel::widgets.dashboard.carts.active_carts.status.active');
                        } elseif ($minutesAgo <= 60) {
                            return __('lunarpanel::widgets.dashboard.carts.active_carts.status.idle');
                        } elseif ($minutesAgo <= 1440) {
                            return __('lunarpanel::widgets.dashboard.carts.active_carts.status.inactive');
                        }

                        return __('lunarpanel::widgets.dashboard.carts.active_carts.status.abandoned');
                    })
                    ->color(fn (string $state): string => match ($state) {
                        __('lunarpanel::widgets.dashboard.carts.active_carts.status.active') => 'success',
                        __('lunarpanel::widgets.dashboard.carts.active_carts.status.idle') => 'info',
                        __('lunarpanel::widgets.dashboard.carts.active_carts.status.inactive') => 'warning',
                        default => 'danger',
                    }),
            ])
            ->paginated(false)
            ->searchable(false)
            ->heading($this->getHeading());
    }
}
