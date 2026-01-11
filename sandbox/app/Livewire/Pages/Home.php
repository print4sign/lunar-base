<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Component;
use Lunar\Models\Article;
use Lunar\Models\Collection;
use Lunar\Models\Product;

class Home extends Component
{
    use HasLocale;

    public function mount(string $locale = 'nl'): void
    {
        $this->initializeLocale($locale);
    }

    public function render()
    {
        $featuredProducts = Product::query()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail', 'productOptions', 'images'])
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
            'articles' => Article::published()
                ->orderBy('published_at', 'desc')
                ->take(4)
                ->get(),
        ])->layout('layouts.storefront', ['title' => __('storefront.nav.home')]);
    }
}
