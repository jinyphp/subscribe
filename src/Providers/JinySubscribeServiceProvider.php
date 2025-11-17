<?php

namespace Jiny\Subscribe\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class JinySubscribeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register any package services here
    }

    public function boot(): void
    {
        // Load views with namespace
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'jiny-subscribe');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../databases/migrations');

        // Load routes
        $this->loadRoutes();

        // Publish assets if needed
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/jiny-subscribe'),
            ], 'jiny-subscribe-views');
        }
    }

    /**
     * Load the package routes
     */
    protected function loadRoutes(): void
    {
        // Load admin routes with admin middleware
        Route::middleware(['web', 'admin'])
            ->group(__DIR__.'/../../routes/admin.php');

        // Load web routes
        Route::middleware(['web'])
            ->group(__DIR__.'/../../routes/web.php');

        // Load home routes
        Route::middleware(['web'])
            ->group(__DIR__.'/../../routes/home.php');
    }
}
