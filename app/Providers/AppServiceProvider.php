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
            \App\Repositories\PlantRepositoryInterface::class,
            \App\Repositories\PlantRepository::class
        );
        $this->app->bind(
            \App\Services\WeatherApiServiceInterface::class,
            \App\Services\WeatherApiService::class
        );
        $this->app->bind(
            \App\Services\WateringCalculatorServiceInterface::class,
            \App\Services\WateringCalculatorService::class
        );
        $this->app->bind(
            \App\Services\PlantApiServiceInterface::class,
            function ($app) {
                return new \App\Services\LoggingPlantApiService(
                    $app->make(\App\Services\PlantApiService::class),
                    $app->make(\Psr\Log\LoggerInterface::class)
                );
            }
        );
        $this->app->bind(
            \App\Services\LoggingServiceInterface::class,
            \App\Services\LaravelLoggingService::class
        );

        $this->app->bind(
            \App\Services\PlantServiceInterface::class,
            function ($app) {
                return new \App\Services\PlantServiceLoggingDecorator(
                    $app->make(\App\Services\PlantService::class),
                    $app->make(\App\Services\LoggingServiceInterface::class)
                );
            }
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
