<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Lunar\Models\Collection;
use Lunar\Models\Product;

class Home extends Component
{
    public function render()
    {
        $featuredProducts = Product::query()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
            ->whereHas('variants.prices')
            ->limit(8)
            ->get();

        $collections = Collection::query()
            ->with(['defaultUrl', 'thumbnail'])
            ->limit(6)
            ->get();

        return view('livewire.pages.home', [
            'featuredProducts' => $featuredProducts,
            'collections' => $collections,
        ])->layout('layouts.storefront', ['title' => 'Home']);
    }
}
