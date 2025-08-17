<?php

use Illuminate\Support\Facades\Route;
use onamfc\LaravelRouteVisualizer\Http\Controllers\RouteVisualizerController;

Route::prefix(config('route-visualizer.route_prefix'))
    ->middleware(config('route-visualizer.middleware'))
    ->group(function () {
        Route::get('/', [RouteVisualizerController::class, 'index'])->name('route-visualizer.dashboard');
        Route::get('/routes', [RouteVisualizerController::class, 'routes'])->name('route-visualizer.routes');
        Route::get('/graph', [RouteVisualizerController::class, 'graph'])->name('route-visualizer.graph');
        Route::get('/tree-data', [RouteVisualizerController::class, 'treeData'])->name('route-visualizer.tree-data');
        Route::post('/clear-cache', [RouteVisualizerController::class, 'clearCache'])->name('route-visualizer.clear-cache');
    });