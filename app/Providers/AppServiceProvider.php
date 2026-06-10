<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Detrás del proxy TLS de Render, generar siempre URLs https
        // (evita contenido mixto y redirecciones http:// innecesarias).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
