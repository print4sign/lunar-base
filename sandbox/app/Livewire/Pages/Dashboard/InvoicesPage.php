<?php

namespace App\Livewire\Pages\Dashboard;

use Livewire\Component;

class InvoicesPage extends Component
{
    public string $locale = 'nl';

    public function mount(string $locale, string $dashboardSegment, string $invoicesSegment): void
    {
        $this->locale = $locale;
        app()->setLocale($locale);
    }

    public function render()
    {
        return view('livewire.pages.dashboard.invoices-page')
            ->layout('layouts.storefront', ['title' => __('dashboard.invoices.title')]);
    }
}
