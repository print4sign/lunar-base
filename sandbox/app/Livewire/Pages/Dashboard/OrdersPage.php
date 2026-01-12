<?php

namespace App\Livewire\Pages\Dashboard;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class OrdersPage extends Component
{
    use WithPagination;

    public string $locale = 'nl';

    public string $filter = 'all';

    public function mount(string $locale, string $dashboardSegment, string $ordersSegment): void
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
    public function orders()
    {
        $query = $this->customer?->orders()
            ->whereNotNull('placed_at')
            ->with(['lines.purchasable', 'shippingAddress', 'currency'])
            ->latest('placed_at');

        if (!$query) {
            return collect();
        }

        if ($this->filter === 'pending') {
            $query->whereIn('status', ['awaiting-payment', 'payment-received', 'pending']);
        } elseif ($this->filter === 'completed') {
            $query->whereIn('status', ['dispatched', 'completed']);
        }

        return $query->paginate(10);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.pages.dashboard.orders-page')
            ->layout('layouts.storefront', ['title' => __('dashboard.orders.title')]);
    }
}
