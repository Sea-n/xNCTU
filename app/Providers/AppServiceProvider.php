<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $socialite = $this->app->make('Laravel\Socialite\Contracts\Factory');
        $socialite->extend(
            'nctu',
            function ($app) use ($socialite) {
                $config = $app['config']['services.nctu'];
                return $socialite->buildProvider(NCTUOAuthProvider::class, $config);
            }
        );
    }
}
