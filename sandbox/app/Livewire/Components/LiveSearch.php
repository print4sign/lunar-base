<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Models\Product;
use Lunar\Search\Facades\Search;

class LiveSearch extends Component
{
    public string $query = '';

    public bool $showResults = false;

    public function updatedQuery(): void
    {
        $this->showResults = strlen($this->query) >= 2;
    }

    #[Computed]
    public function results()
    {
        if (strlen($this->query) < 2) {
            return collect();
        }

        try {
            // Use Lunar Search for better results
            $searchResults = Search::on(Product::class)
                ->query($this->query)
                ->get();

            if (!$searchResults || !isset($searchResults->hits)) {
                return $this->fallbackSearch();
            }

            $productIds = collect($searchResults->hits)->pluck('id')->take(6)->toArray();

            // Fetch full product models with relationships
            return Product::query()
                ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
                ->whereIn('id', $productIds)
                ->where('status', 'published')
                ->whereHas('variants.prices')
                ->get()
                // Maintain search result order
                ->sortBy(function ($product) use ($productIds) {
                    return array_search($product->id, $productIds);
                })
                ->values();
        } catch (\Exception $e) {
            // Fallback to database search if Lunar Search fails
            return $this->fallbackSearch();
        }
    }

    private function fallbackSearch()
    {
        $searchTerm = '%' . $this->query . '%';

        return Product::query()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
            ->whereHas('variants.prices')
            ->where('status', 'published')
            ->where(function ($q) use ($searchTerm) {
                $q->where('attribute_data', 'like', $searchTerm)
                  ->orWhereHas('variants', function($variantQuery) use ($searchTerm) {
                      $variantQuery->where('sku', 'like', $searchTerm);
                  });
            })
            ->limit(6)
            ->get();
    }

    public function clearSearch(): void
    {
        $this->query = '';
        $this->showResults = false;
    }

    public function goToSearch(): void
    {
        $this->redirect(localizedUrl('search.view').'?q='.urlencode($this->query));
    }

    public function render()
    {
        return view('livewire.components.live-search');
    }
}
