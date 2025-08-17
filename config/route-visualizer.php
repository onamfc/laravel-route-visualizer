<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Visualizer Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ROUTE_VISUALIZER_ENABLED', app()->environment(['local', 'testing'])),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'route-visualizer',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key' => 'route_visualizer_data',
    ],

    /*
    |--------------------------------------------------------------------------
    | Visualization Settings
    |--------------------------------------------------------------------------
    */
    'visualization' => [
        'library' => 'vis', // vis, d3, mermaid
        'theme' => 'light', // light, dark
        'layout' => 'hierarchical', // hierarchical, network
        'pagination' => [
            'enabled' => true,
            'per_page' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    */
    'export' => [
        'path' => storage_path('app/route-maps'),
        'formats' => ['html', 'json', 'svg'],
        'templates' => ['default', 'minimal', 'detailed'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'hide_sensitive_routes' => true,
        'sensitive_patterns' => [
            'admin/*',
            'api/admin/*',
            '*/password/*',
            'telescope/*',
            'horizon/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Settings
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'check_duplicates' => true,
        'check_missing_controllers' => true,
        'check_missing_methods' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'telescope' => [
            'enabled' => class_exists(\Laravel\Telescope\TelescopeServiceProvider::class),
            'base_url' => '/telescope',
        ],
    ],
];