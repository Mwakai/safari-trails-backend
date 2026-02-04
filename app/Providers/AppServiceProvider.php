<?php

namespace App\Providers;

use App\Models\Trail;
use App\Observers\TrailObserver;
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
        Trail::observe(TrailObserver::class);
    }
}
