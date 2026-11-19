<?php

namespace App\Providers;

use App\Services\CourtData\CourtDataProviderRegistry;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            CourtDataProviderRegistry::class,
            function ($app) {
                return new CourtDataProviderRegistry(
                    $app,
                    config('court-data.providers', [])
                );
            }
        );
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        //
    }
}
