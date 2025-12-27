<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Product;

class ProductsIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $sort = 'newest';

    #[Url]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Product::query()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
            ->whereHas('variants.prices');

        if ($this->search) {
            $query->whereHas('attribute_data', function ($q) {
                $q->where('attribute_data', 'like', '%' . $this->search . '%');
            });
        }

        $query = match ($this->sort) {
            'price_asc' => $query->orderBy(
                \Lunar\Models\Price::select('price')
                    ->whereColumn('priceable_id', 'lunar_products.id')
                    ->where('priceable_type', Product::class)
                    ->limit(1)
            ),
            'price_desc' => $query->orderByDesc(
                \Lunar\Models\Price::select('price')
                    ->whereColumn('priceable_id', 'lunar_products.id')
                    ->where('priceable_type', Product::class)
                    ->limit(1)
            ),
            'name_asc' => $query->orderBy('attribute_data->name->value'),
            'name_desc' => $query->orderByDesc('attribute_data->name->value'),
            default => $query->latest(),
        };

        $products = $query->paginate(12);

        return view('livewire.pages.products-index', [
            'products' => $products,
        ])->layout('layouts.storefront', ['title' => 'Products']);
    }
}
