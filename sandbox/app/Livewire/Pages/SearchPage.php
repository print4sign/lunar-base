<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Product;

class SearchPage extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $query = '';

    public function updatingQuery(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = collect();

        if (strlen($this->query) >= 2) {
            $products = Product::query()
                ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
                ->whereHas('variants.prices')
                ->where(function ($q) {
                    $q->where('attribute_data', 'like', '%' . $this->query . '%');
                })
                ->paginate(12);
        }

        return view('livewire.pages.search-page', [
            'products' => $products,
        ])->layout('layouts.storefront', ['title' => 'Search']);
    }
}
