<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PrintService;

class PrintServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PrintService::class, function ($app) {
            return new PrintService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
