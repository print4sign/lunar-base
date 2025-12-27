<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Lunar\Models\Order;

class CheckoutSuccessPage extends Component
{
    public Order $order;

    public function mount(int $order): void
    {
        $this->order = Order::with(['lines.purchasable', 'shippingAddress', 'billingAddress'])
            ->findOrFail($order);
    }

    public function render()
    {
        return view('livewire.pages.checkout-success-page')
            ->layout('layouts.storefront', ['title' => 'Order Confirmed']);
    }
}
