<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Inspiration;

class InspirationsIndex extends Component
{
    use HasLocale;
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public int $rating = 0;

    public function mount(string $locale = 'nl'): void
    {
        $this->initializeLocale($locale);
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingRating(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function featuredInspirations()
    {
        return Inspiration::approved()
            ->published()
            ->featured()
            ->with(['media', 'orderLine.purchasable.product'])
            ->latest('published_at')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function averageRating()
    {
        return Inspiration::approved()->avg('rating');
    }

    #[Computed]
    public function totalReviews()
    {
        return Inspiration::approved()->count();
    }

    public function render()
    {
        $query = Inspiration::approved()
            ->published()
            ->with(['media', 'orderLine.purchasable.product', 'order'])
            ->latest('published_at');

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->rating > 0) {
            $query->where('rating', '>=', $this->rating);
        }

        $inspirations = $query->paginate(config('lunar.inspirations.display.per_page', 12));

        return view('livewire.pages.inspirations-index', [
            'inspirations' => $inspirations,
        ])->layout('layouts.storefront', ['title' => __('inspiration.index.title')]);
    }
}
