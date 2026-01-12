<?php

namespace App\Livewire\Pages\Dashboard;

use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Order;

class OrderDetailPage extends Component
{
    public string $locale = 'nl';

    public Order $order;

    public function mount(string $locale, string $dashboardSegment, string $ordersSegment, Order $order): void
    {
        $this->locale = $locale;
        app()->setLocale($locale);

        // Verify order belongs to current customer
        $customer = auth()->user()->latestCustomer();
        abort_unless($order->customer_id === $customer?->id, 403);

        $this->order = $order->load([
            'lines.purchasable',
            'shippingAddress',
            'billingAddress',
            'transactions',
            'currency',
        ]);
    }

    public function reorder(): void
    {
        $cart = CartSession::manager();

        foreach ($this->order->productLines as $line) {
            if ($line->purchasable) {
                $cart->add($line->purchasable, $line->quantity, $line->meta ?? []);
            }
        }

        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart');
        session()->flash('success', __('dashboard.orders.reorder_success'));
    }

    public function render()
    {
        return view('livewire.pages.dashboard.order-detail-page')
            ->layout('layouts.storefront', ['title' => __('dashboard.orders.order_detail', ['reference' => $this->order->reference])]);
    }
}
