<?php

namespace onamfc\LaravelRouteVisualizer;

use Illuminate\Support\ServiceProvider;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;
use onamfc\LaravelRouteVisualizer\Console\Commands\ExportRoutesCommand;
use onamfc\LaravelRouteVisualizer\Console\Commands\InstallCommand;

class RouteVisualizerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/route-visualizer.php', 'route-visualizer');

        $this->app->singleton(RouteScanner::class);

        $this->commands([
            ExportRoutesCommand::class,
            InstallCommand::class,
        ]);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/route-visualizer.php' => config_path('route-visualizer.php'),
            ], 'route-visualizer-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/route-visualizer'),
            ], 'route-visualizer-views');

            $this->publishes([
                __DIR__ . '/../resources/assets' => public_path('vendor/route-visualizer'),
            ], 'route-visualizer-assets');
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'route-visualizer');

        if (config('route-visualizer.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}