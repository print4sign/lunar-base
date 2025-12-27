<?php

namespace App\Livewire\Components;

use Livewire\Attributes\On;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

class CartSidebar extends Component
{
    public bool $open = false;

    public ?Cart $cart = null;

    public function mount(): void
    {
        $this->loadCart();
    }

    #[On('toggle-cart')]
    public function toggleCart(): void
    {
        $this->open = !$this->open;
        $this->loadCart();
    }

    #[On('cart-updated')]
    public function loadCart(): void
    {
        $this->cart = CartSession::current()?->calculate();
    }

    public function updateQuantity(int $lineId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeLine($lineId);
            return;
        }

        $cart = CartSession::current();
        $line = $cart->lines->find($lineId);

        if ($line) {
            $line->update(['quantity' => $quantity]);
            $this->dispatch('cart-updated');
            $this->loadCart();
        }
    }

    public function removeLine(int $lineId): void
    {
        $cart = CartSession::current();
        $cart->lines()->where('id', $lineId)->delete();

        $this->dispatch('cart-updated');
        $this->loadCart();
    }

    public function render()
    {
        return view('livewire.components.cart-sidebar');
    }
}
