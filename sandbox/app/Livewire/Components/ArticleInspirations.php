<?php

namespace App\Livewire\Components;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Lunar\Models\Article;

class ArticleInspirations extends Component
{
    public Article $article;

    #[Computed]
    public function inspirations()
    {
        return $this->article->inspirations()
            ->approved()
            ->with('media')
            ->orderByPivot('position')
            ->get();
    }

    public function render()
    {
        return view('livewire.components.article-inspirations');
    }
}
