<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class ProductPage extends Component
{
    #[Locked]
    public Product $product;

    public ?ProductVariant $selectedVariant = null;

    public int $quantity = 1;

    public array $selectedOptions = [];

    public bool $added = false;

    public function mount(string $slug): void
    {
        $this->product = Product::query()
            ->with([
                'variants.prices.currency',
                'variants.values.option',
                'productOptions.values',
                'media',
                'thumbnail',
            ])
            ->whereHas('urls', fn ($q) => $q->where('slug', $slug))
            ->orWhere('id', $slug)
            ->firstOrFail();

        $this->selectedVariant = $this->product->variants->first();

        // Initialize selected options from first variant
        if ($this->selectedVariant) {
            foreach ($this->selectedVariant->values as $value) {
                $this->selectedOptions[$value->option->id] = $value->id;
            }
        }
    }

    public function updatedSelectedOptions(): void
    {
        // Find variant that matches all selected options
        $this->selectedVariant = $this->product->variants->first(function ($variant) {
            $variantValueIds = $variant->values->pluck('id')->toArray();

            foreach ($this->selectedOptions as $valueId) {
                if (!in_array($valueId, $variantValueIds)) {
                    return false;
                }
            }

            return true;
        });
    }

    public function incrementQuantity(): void
    {
        $this->quantity++;
    }

    public function decrementQuantity(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function addToCart(): void
    {
        if (!$this->selectedVariant) {
            return;
        }

        $cart = CartSession::current() ?? CartSession::create();
        $cart->add($this->selectedVariant, $this->quantity);

        $this->dispatch('cart-updated');
        $this->dispatch('toggle-cart');

        $this->added = true;
        $this->quantity = 1;

        $this->js('setTimeout(() => $wire.set("added", false), 2000)');
    }

    public function render()
    {
        $price = $this->selectedVariant?->prices->first();

        return view('livewire.pages.product-page', [
            'price' => $price,
        ])->layout('layouts.storefront', [
            'title' => $this->product->translateAttribute('name'),
        ]);
    }
}
