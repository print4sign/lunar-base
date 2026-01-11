<?php

namespace Lunar\Scraper;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Lunar\Admin\Support\Facades\AttributeData;
use Lunar\Scraper\Admin\FieldTypes\RepeaterFieldType;
use Lunar\Scraper\Admin\FieldTypes\TranslatedCodeFieldType;
use Lunar\Scraper\Admin\FieldTypes\TranslatedRepeaterFieldType;
use Lunar\Scraper\Admin\Synthesizers\TranslatedCodeSynth;
use Lunar\Scraper\Admin\Synthesizers\TranslatedRepeaterSynth;
use Lunar\Scraper\Console\Commands\DownloadProductImages;
use Lunar\Scraper\Console\Commands\ImportScrapedProducts;
use Lunar\Scraper\Console\Commands\InstallScraperScripts;
use Lunar\Scraper\Console\Commands\RewriteArticlesCommand;
use Lunar\Scraper\Console\Commands\ScrapeArticlesCommand;
use Lunar\Scraper\Console\Commands\ScrapeProbo;
use Lunar\Scraper\Console\Commands\ScrapeProboAll;
use Lunar\Scraper\FieldTypes\RepeaterFieldValue;
use Lunar\Scraper\FieldTypes\TranslatedCodeFieldValue;
use Lunar\Scraper\FieldTypes\TranslatedRepeaterFieldValue;

class ScraperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings needed - services are instantiated directly
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scraper.php', 'lunar.scraper');

        $this->publishes([
            __DIR__.'/../config/scraper.php' => config_path('lunar/scraper.php'),
        ], 'lunar.scraper.config');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'lunarscraper');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lunarscraper');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/lunarscraper'),
        ], 'lunar.scraper.translations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ScrapeProbo::class,
                ScrapeProboAll::class,
                ScrapeArticlesCommand::class,
                RewriteArticlesCommand::class,
                ImportScrapedProducts::class,
                DownloadProductImages::class,
                InstallScraperScripts::class,
            ]);
        }

        // Register custom field types for Lunar Admin
        $this->registerFieldTypes();
    }

    /**
     * Register custom field types and synthesizers.
     */
    protected function registerFieldTypes(): void
    {
        // Register Livewire synthesizers
        Livewire::propertySynthesizer(TranslatedRepeaterSynth::class);
        Livewire::propertySynthesizer(TranslatedCodeSynth::class);

        // Register admin field types with Lunar
        if (class_exists(AttributeData::class)) {
            AttributeData::registerFieldType(RepeaterFieldValue::class, RepeaterFieldType::class);
            AttributeData::registerFieldType(TranslatedRepeaterFieldValue::class, TranslatedRepeaterFieldType::class);
            AttributeData::registerFieldType(TranslatedCodeFieldValue::class, TranslatedCodeFieldType::class);
        }
    }
}
