<?php

namespace App\Livewire\Pages;

use App\Livewire\Concerns\HasLocale;
use Livewire\Component;

class StaticPage extends Component
{
    use HasLocale;

    public string $pageKey;

    public function mount(string $locale = 'nl', string $pageKey = ''): void
    {
        $this->initializeLocale($locale);
        $this->pageKey = $pageKey;
    }

    public function render()
    {
        $viewName = 'livewire.pages.static.'.$this->pageKey;

        // Fallback to generic static page if specific view doesn't exist
        if (! view()->exists($viewName)) {
            $viewName = 'livewire.pages.static.coming-soon';
        }

        return view($viewName)
            ->layout('layouts.storefront', ['title' => $this->getTitle()]);
    }

    protected function getTitle(): string
    {
        $titles = [
            'nl' => [
                'faq' => 'Veelgestelde vragen',
                'shipping' => 'Verzending & levertijden',
                'returns' => 'Retourbeleid',
                'about' => 'Over Drukhoek',
                'careers' => 'Werken bij Drukhoek',
                'terms' => 'Algemene voorwaarden',
                'privacy' => 'Privacybeleid',
                'cookies' => 'Cookiebeleid',
                'delivery-specs' => 'Aanleverspecificaties',
            ],
            'en' => [
                'faq' => 'FAQ',
                'shipping' => 'Shipping & delivery',
                'returns' => 'Returns policy',
                'about' => 'About Drukhoek',
                'careers' => 'Careers',
                'terms' => 'Terms & conditions',
                'privacy' => 'Privacy policy',
                'cookies' => 'Cookie policy',
                'delivery-specs' => 'File Specifications',
            ],
            'de' => [
                'faq' => 'Häufige Fragen',
                'shipping' => 'Versand & Lieferung',
                'returns' => 'Rückgaberecht',
                'about' => 'Über Drukhoek',
                'careers' => 'Karriere',
                'terms' => 'AGB',
                'privacy' => 'Datenschutz',
                'cookies' => 'Cookie-Richtlinie',
                'delivery-specs' => 'Dateispezifikationen',
            ],
            'es' => [
                'faq' => 'Preguntas frecuentes',
                'shipping' => 'Envío y entrega',
                'returns' => 'Política de devoluciones',
                'about' => 'Sobre Drukhoek',
                'careers' => 'Empleo',
                'terms' => 'Términos y condiciones',
                'privacy' => 'Política de privacidad',
                'cookies' => 'Política de cookies',
                'delivery-specs' => 'Especificaciones de archivos',
            ],
        ];

        $locale = app()->getLocale();

        return $titles[$locale][$this->pageKey] ?? ucfirst($this->pageKey);
    }
}
