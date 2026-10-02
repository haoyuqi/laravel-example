<?php

namespace App\Providers;

use App\Libraries\BingWallpaper\BingWallpaper;
use App\Libraries\BingWallpaper\Contracts\BingWallpaperInterface;
use App\Libraries\GetCityByIp\FreeAPI;
use App\Libraries\GetCityByIp\GeoIP;
use App\Libraries\GetCityByIp\GetCityByIpAbstract;
use App\Models\BlackList;
use App\Observers\BlackListObserver;
use App\Support\RedisFailureLogger;
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
        /*if ($this->app->isLocal()) {
            $this->app->register(TelescopeServiceProvider::class);
        }*/
        $this->app->register(TelescopeServiceProvider::class);
        $this->app->singleton(RedisFailureLogger::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(GetCityByIpAbstract::class, function ($app) {
            return new GeoIP(new Client);
            //            return new FreeAPI(new Client());
        });

        $this->app->bind(
            BingWallpaperInterface::class,
            BingWallpaper::class
        );

        BlackList::observe(BlackListObserver::class);

    }
}
