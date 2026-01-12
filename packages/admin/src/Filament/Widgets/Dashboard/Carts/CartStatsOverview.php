<?php

namespace Lunar\Admin\Filament\Widgets\Dashboard\Carts;

use Carbon\Carbon;
use Filament\Support\Facades\FilamentIcon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lunar\Models\Cart;

class CartStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $now = now();

        // Active carts (with items, no completed orders)
        $activeCarts = Cart::query()
            ->whereNull('deleted_at')
            ->whereHas('lines')
            ->where(function ($query) {
                $query->whereDoesntHave('orders')
                    ->orWhereHas('orders', fn ($q) => $q->whereNull('placed_at'));
            })
            ->count();

        // Carts updated in last 15 minutes (real-time active sessions)
        $realtimeActive = Cart::query()
            ->whereNull('deleted_at')
            ->whereHas('lines')
            ->where('updated_at', '>=', $now->copy()->subMinutes(15))
            ->where(function ($query) {
                $query->whereDoesntHave('orders')
                    ->orWhereHas('orders', fn ($q) => $q->whereNull('placed_at'));
            })
            ->count();

        // Abandoned carts (not updated in last 24 hours, has items, no order)
        $abandonedCarts = Cart::query()
            ->whereNull('deleted_at')
            ->whereHas('lines')
            ->where('updated_at', '<', $now->copy()->subHours(24))
            ->whereDoesntHave('orders')
            ->count();

        // Carts created today
        $cartsToday = Cart::query()
            ->whereNull('deleted_at')
            ->whereHas('lines')
            ->whereDate('created_at', $now->toDateString())
            ->count();

        return [
            Stat::make(
                label: __('lunarpanel::widgets.dashboard.carts.stats.realtime_active'),
                value: number_format($realtimeActive)
            )
                ->description(__('lunarpanel::widgets.dashboard.carts.stats.realtime_active_description'))
                ->descriptionIcon(FilamentIcon::resolve('heroicon-o-signal') ?? 'heroicon-o-signal')
                ->color($realtimeActive > 0 ? 'success' : 'gray'),

            Stat::make(
                label: __('lunarpanel::widgets.dashboard.carts.stats.active_carts'),
                value: number_format($activeCarts)
            )
                ->description(__('lunarpanel::widgets.dashboard.carts.stats.active_carts_description'))
                ->descriptionIcon(FilamentIcon::resolve('heroicon-o-shopping-cart') ?? 'heroicon-o-shopping-cart')
                ->color('primary'),

            Stat::make(
                label: __('lunarpanel::widgets.dashboard.carts.stats.abandoned_carts'),
                value: number_format($abandonedCarts)
            )
                ->description(__('lunarpanel::widgets.dashboard.carts.stats.abandoned_carts_description'))
                ->descriptionIcon(FilamentIcon::resolve('heroicon-o-exclamation-triangle') ?? 'heroicon-o-exclamation-triangle')
                ->color($abandonedCarts > 0 ? 'warning' : 'gray'),

            Stat::make(
                label: __('lunarpanel::widgets.dashboard.carts.stats.carts_today'),
                value: number_format($cartsToday)
            )
                ->description(__('lunarpanel::widgets.dashboard.carts.stats.carts_today_description'))
                ->descriptionIcon(FilamentIcon::resolve('heroicon-o-calendar') ?? 'heroicon-o-calendar')
                ->color('info'),
        ];
    }
}
