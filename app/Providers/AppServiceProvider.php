<?php

namespace App\Providers;

use App\Libraries\GetCityByIp\FreeAPI;
use App\Libraries\GetCityByIp\GetCityByIpAbstract;
use GuzzleHttp\Client;
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
        if ($this->app->isLocal()) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(GetCityByIpAbstract::class, function ($app) {
            return new FreeAPI(new Client());
        });
    }
}
