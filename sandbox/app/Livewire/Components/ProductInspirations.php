<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Models\Inspiration;
use Lunar\Models\Product;

class ProductInspirations extends Component
{
    public Product $product;
    public int $limit = 6;

    #[Computed]
    public function inspirations()
    {
        return $this->product->approvedInspirations()
            ->with('media')
            ->latest('published_at')
            ->take($this->limit)
            ->get();
    }

    #[Computed]
    public function averageRating(): ?float
    {
        return $this->product->getAverageRating();
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->product->getReviewCount();
    }

    public function render()
    {
        return view('livewire.components.product-inspirations');
    }
}
