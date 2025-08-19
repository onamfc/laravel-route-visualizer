<?php

namespace onamfc\LaravelRouteVisualizer;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;
use onamfc\LaravelRouteVisualizer\Console\Commands\InstallCommand;
use onamfc\LaravelRouteVisualizer\Console\Commands\ExportRoutesCommand;

class RouteVisualizerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/route-visualizer.php',
            'route-visualizer'
        );

        $this->app->singleton(RouteScanner::class, function ($app) {
            return new RouteScanner();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!config('route-visualizer.enabled', false)) {
            return;
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'route-visualizer');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/route-visualizer.php' => config_path('route-visualizer.php'),
            ], 'route-visualizer-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/route-visualizer'),
            ], 'route-visualizer-views');

            $this->commands([
                InstallCommand::class,
                ExportRoutesCommand::class,
            ]);
        }
    }
}