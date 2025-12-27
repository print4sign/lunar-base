<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

class AddToCart extends Component
{
    #[Locked]
    public ProductVariant $variant;

    public int $quantity = 1;

    public bool $added = false;

    public function addToCart(): void
    {
        $cart = CartSession::current() ?? CartSession::create();

        $cart->add($this->variant, $this->quantity);

        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart');

        $this->added = true;

        // Reset added state after 2 seconds
        $this->js('setTimeout(() => $wire.set("added", false), 2000)');
    }

    public function render()
    {
        return view('livewire.components.add-to-cart');
    }
}
