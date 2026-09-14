<?php

namespace App\Providers;

use App\Support\LocaleAlternates;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Una instancia POR REQUEST: los controllers de ficha declaran ahi
        // las URLs del selector de idioma y el header las lee mas tarde, en
        // el render de la misma respuesta. `scoped` y no `singleton` a
        // proposito -- ver el docblock de App\Support\LocaleAlternates.
        $this->app->scoped(LocaleAlternates::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
