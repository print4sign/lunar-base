<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Collection;

class CollectionPage extends Component
{
    use WithPagination;

    #[Locked]
    public Collection $collection;

    #[Url]
    public string $sort = 'newest';

    public function mount(string $slug): void
    {
        $this->collection = Collection::query()
            ->with(['thumbnail'])
            ->whereHas('urls', fn ($q) => $q->where('slug', $slug))
            ->orWhere('id', $slug)
            ->firstOrFail();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = $this->collection->products()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
            ->whereHas('variants.prices');

        $query = match ($this->sort) {
            'name_asc' => $query->orderBy('attribute_data->name->value'),
            'name_desc' => $query->orderByDesc('attribute_data->name->value'),
            default => $query->latest(),
        };

        $products = $query->paginate(12);

        return view('livewire.pages.collection-page', [
            'products' => $products,
        ])->layout('layouts.storefront', [
            'title' => $this->collection->translateAttribute('name'),
        ]);
    }
}
