<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->bind(
            \App\Services\PlantApiServiceInterface::class,
            \App\Services\PlantApiService::class
        );
        $this->app->bind(
            \App\Services\WeatherApiServiceInterface::class,
            \App\Services\WeatherApiService::class
        );
        $this->app->bind(
            \App\Services\WateringCalculatorServiceInterface::class,
            \App\Services\WateringCalculatorService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }
}
