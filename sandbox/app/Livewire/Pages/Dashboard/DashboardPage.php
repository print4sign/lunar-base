<?php

namespace App\Livewire\Pages\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Component;

class DashboardPage extends Component
{
    public string $locale = 'nl';

    public function mount(string $locale, string $dashboardSegment): void
    {
        $this->locale = $locale;
        app()->setLocale($locale);
    }

    #[Computed]
    public function customer()
    {
        return auth()->user()->latestCustomer();
    }

    #[Computed]
    public function recentOrders()
    {
        return $this->customer?->orders()
            ->whereNotNull('placed_at')
            ->with(['lines', 'currency'])
            ->latest('placed_at')
            ->limit(5)
            ->get() ?? collect();
    }

    #[Computed]
    public function orderCount()
    {
        return $this->customer?->orders()
            ->whereNotNull('placed_at')
            ->count() ?? 0;
    }

    #[Computed]
    public function addressCount()
    {
        return $this->customer?->addresses()->count() ?? 0;
    }

    #[Computed]
    public function savedCartCount()
    {
        return auth()->user()->savedCarts()->count();
    }

    public function render()
    {
        return view('livewire.pages.dashboard.dashboard-page')
            ->layout('layouts.storefront', ['title' => __('dashboard.overview.title')]);
    }
}
