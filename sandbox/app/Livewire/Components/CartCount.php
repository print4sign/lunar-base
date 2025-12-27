<?php

namespace App\Livewire\Components;

use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Facades\CartSession;

class CartCount extends Component
{
    public int $count = 0;

    public function mount(): void
    {
        $this->updateCount();
    }

    #[On('cart-updated')]
    public function updateCount(): void
    {
        $cart = CartSession::current();
        $this->count = $cart?->lines->sum('quantity') ?? 0;
    }

    public function render()
    {
        return view('livewire.components.cart-count');
    }
}
