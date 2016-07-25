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
        $this->publishes([__DIR__.'/config/plaid.php' => config_path('plaid.php')], 'config');
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
