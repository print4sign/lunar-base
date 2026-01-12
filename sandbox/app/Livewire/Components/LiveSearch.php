<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Models\Product;

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

        // Ensure locale is set for product translations
        $currentLocale = app()->getLocale();

        return Product::query()
            ->with(['defaultUrl', 'variants.prices.currency', 'thumbnail'])
            ->whereHas('variants.prices')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('attribute_data', 'like', '%'.$this->query.'%');
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
