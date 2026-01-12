<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Component;
use Lunar\Models\Article;

class ArticlePage extends Component
{
    use HasLocale;

    public Article $article;

    public function mount(string $locale = 'nl', ?string $articlesSegment = null, string $slug = ''): void
    {
        $this->initializeLocale($locale);

        $this->article = Article::published()
            ->where('slug->'.$locale, $slug)
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.pages.article-page', [
            'article' => $this->article,
        ])->layout('layouts.storefront', ['title' => $this->article->getTitle()]);
    }
}
