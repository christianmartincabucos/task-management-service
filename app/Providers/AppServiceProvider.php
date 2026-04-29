<?php

namespace App\Providers;

use App\Repositories\TranslationRepository;
use App\Services\TranslationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind repository as singleton for performance
        $this->app->singleton(TranslationRepository::class, function () {
            return new TranslationRepository();
        });

        // Bind service with injected repository
        $this->app->singleton(TranslationService::class, function ($app) {
            return new TranslationService(
                $app->make(TranslationRepository::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
