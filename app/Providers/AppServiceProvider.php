<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // money() and other small helpers. Loaded here (as well as by Composer)
        // so they work even where `composer dump-autoload` was not re-run.
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
