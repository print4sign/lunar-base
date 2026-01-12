<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Article;

class ArticlesIndex extends Component
{
    use HasLocale;
    use WithPagination;

    #[Url]
    public string $category = '';

    public function mount(string $locale = 'nl', ?string $articlesSegment = null): void
    {
        $this->initializeLocale($locale);
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Article::published()
            ->orderBy('published_at', 'desc');

        if ($this->category) {
            $query->where('category', $this->category);
        }

        $articles = $query->paginate(12);

        return view('livewire.pages.articles-index', [
            'articles' => $articles,
        ])->layout('layouts.storefront', ['title' => __('article.index.title')]);
    }
}
