<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Component;

class ContactPage extends Component
{
    use HasLocale;

    public function mount(string $locale = 'nl'): void
    {
        $this->initializeLocale($locale);
    }

    public function render()
    {
        return view('livewire.pages.contact-page')
            ->layout('layouts.storefront', ['title' => __('storefront.nav.contact')]);
    }
}
