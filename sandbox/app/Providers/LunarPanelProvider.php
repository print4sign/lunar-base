<?php

namespace App\Providers;

use Filament\Panel;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\LunarPanel;

class LunarPanelProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register the panel early, before migrations run
        $this->app->booting(function () {
            LunarPanel::panel(
                fn (Panel $panel) => $panel->path('admin')
            )->register();
        });
    }

    public function boot(): void
    {
        //
    }
}
