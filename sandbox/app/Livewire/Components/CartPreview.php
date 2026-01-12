<?php

namespace App\Livewire\Components;

use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

class CartPreview extends Component
{
    public ?Cart $cart = null;

    public function mount(): void
    {
        $this->loadCart();
    }

    #[On('cart-updated')]
    #[On('cart-cleared')]
    public function loadCart(): void
    {
        $this->cart = CartSession::current()?->calculate();
    }

    public function render()
    {
        return view('livewire.components.cart-preview');
    }
}
