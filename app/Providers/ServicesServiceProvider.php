<?php

namespace App\Providers;

use App\Services\Discogs;
use App\Services\Wikipedia;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Discogs::class, function (Application $app) {
            return new Discogs(
                $app->make('config')->get('services.discogs.token'),
            );
        });

        $this->app->singleton(Wikipedia::class);

    }
}
