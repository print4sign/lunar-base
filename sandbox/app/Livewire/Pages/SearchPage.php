<?php

namespace App\Livewire\Pages;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Product;
use Lunar\Search\Facades\Search;

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
        $results = null;
        $products = collect();

        if (strlen($this->query) >= 2) {
            // Use Lunar Search for better results
            try {
                $results = Search::on(Product::class)
                    ->query($this->query)
                    ->get();

                // Extract products from search results
                if ($results && isset($results->hits)) {
                    $productIds = collect($results->hits)->pluck('id')->toArray();

                    // Fetch full product models with relationships
                    $products = Product::query()
                        ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
                        ->whereIn('id', $productIds)
                        ->where('status', 'published')
                        ->whereHas('variants.prices')
                        ->get()
                        // Maintain search result order
                        ->sortBy(function ($product) use ($productIds) {
                            return array_search($product->id, $productIds);
                        });
                }
            } catch (\Exception $e) {
                // Fallback to database search if Lunar Search fails
                $searchTerm = '%' . $this->query . '%';

                $products = Product::query()
                    ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
                    ->whereHas('variants.prices')
                    ->where('status', 'published')
                    ->where(function ($q) use ($searchTerm) {
                        $q->where('attribute_data', 'like', $searchTerm)
                          ->orWhereHas('variants', function($variantQuery) use ($searchTerm) {
                              $variantQuery->where('sku', 'like', $searchTerm);
                          });
                    })
                    ->limit(12)
                    ->get();
            }
        }

        return view('livewire.pages.search-page', [
            'products' => $products,
            'results' => $results,
        ])->layout('layouts.storefront', ['title' => 'Search Results']);
    }
}
