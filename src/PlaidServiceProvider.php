<?php

namespace Travoltron\Plaid;

use Illuminate\Support\ServiceProvider;

class PlaidServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Load up routes
        include __DIR__.'/routes/routes.php';
        // Copy config file to config directory
        $this->publishes([__DIR__.'/config/plaid.php' => config_path('plaid.php')], 'config');
        // Publish tests to be run as part of parent install
        $this->publishes([
            __DIR__.'/../tests/PlaidTest.php' => base_path('tests/PlaidTest.php')
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('plaid', function ($app) {
            return new Plaid($app);
        });
    }
}
