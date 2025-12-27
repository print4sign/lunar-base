<?php

namespace App\Livewire\Pages;

use Livewire\Component;
use Lunar\Models\Collection;

class CollectionsIndex extends Component
{
    public function render()
    {
        $collections = Collection::query()
            ->with(['defaultUrl', 'thumbnail'])
            ->get();

        return view('livewire.pages.collections-index', [
            'collections' => $collections,
        ])->layout('layouts.storefront', ['title' => 'Collections']);
    }
}
