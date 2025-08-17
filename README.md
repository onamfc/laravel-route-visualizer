# Laravel Route Visualizer

A comprehensive route visualization package for Laravel applications that provides an interactive web interface to explore, analyze, and export your application's routing structure with advanced validation and multiple visualization options.

## Features

- **Interactive Route Explorer**: Web-based dashboard with advanced search and filtering
- **Multiple Visualization Libraries**: Choose between Vis.js, D3.js, or Mermaid.js for network graphs
- **Tree View**: Hierarchical visualization of route structure organized by prefixes
- **Responsive Design**: Works perfectly on desktop and mobile devices with dark/light themes
- **Advanced Filtering**: Filter by HTTP methods, middleware, middleware groups, controllers, domains, namespaces, and more
- **Route Validation**: Automatic detection of duplicate routes, missing controllers, and missing methods
- **Export Capabilities**: Export filtered route data to HTML, JSON, or SVG formats
- **Performance Optimized**: Built-in caching, pagination, and lazy loading for large route sets
- **Security Aware**: Hide sensitive routes in production environments
- **Customizable**: Dark/light themes, configurable visualization options, and multiple templates
- **Copy to Clipboard**: One-click copying of route URIs and controller actions
- **Laravel Telescope Integration**: Direct links to route performance data when Telescope is installed

## Requirements

- PHP ^8.1
- Laravel ^10.0|^11.0|^12.0

## Installation

Install the package via Composer:

```bash
composer require onamfc/laravel-route-visualizer
```

The package will automatically register itself via Laravel's auto-discovery feature.

### Quick Installation

Use the install command to publish all necessary files:

```bash
php artisan route-visualizer:install
```

This command will publish the configuration file, views, and assets in one step.

### Manual Installation

If you prefer to publish files individually:

#### Publish Configuration

```bash
php artisan vendor:publish --tag=route-visualizer-config
```

#### Publish Views (Optional)

If you want to customize the views:

```bash
php artisan vendor:publish --tag=route-visualizer-views
```

#### Publish Assets (Optional)

If you want to serve assets locally:

```bash
php artisan vendor:publish --tag=route-visualizer-assets
```

## Configuration

The configuration file `config/route-visualizer.php` allows you to customize:

```php
return [
    // Enable/disable the visualizer (auto-disabled in production)
    'enabled' => env('ROUTE_VISUALIZER_ENABLED', app()->environment(['local', 'testing'])),
    
    // Route prefix for the visualizer dashboard
    'route_prefix' => 'route-visualizer',
    
    // Middleware to apply to visualizer routes
    'middleware' => ['web'],
    
    // Cache settings for performance
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
        'key' => 'route_visualizer_data',
    ],
    
    // Visualization library and options
    'visualization' => [
        'library' => 'vis', // vis, d3, mermaid
        'theme' => 'light', // light, dark
        'layout' => 'hierarchical', // hierarchical, network
        'pagination' => [
            'enabled' => true,
            'per_page' => 50,
        ],
    ],
    
    // Export settings
    'export' => [
        'path' => storage_path('app/route-maps'),
        'formats' => ['html', 'json', 'svg'],
        'templates' => ['default', 'minimal', 'detailed'],
    ],
    
    // Security settings
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
    
    // Validation settings
    'validation' => [
        'check_duplicates' => true,
        'check_missing_controllers' => true,
        'check_missing_methods' => true,
    ],
    
    // Integration settings
    'integrations' => [
        'telescope' => [
            'enabled' => class_exists(\Laravel\Telescope\TelescopeServiceProvider::class),
            'base_url' => '/telescope',
        ],
    ],
];
```

## Usage

### Web Interface

Once installed, visit the route visualizer dashboard:

```
http://your-app.com/route-visualizer
```

The dashboard provides:

- **Overview Statistics**: Total routes, controllers, middleware, domains, namespaces, and validation issues
- **Advanced Search & Filtering**: Real-time search with filters for methods, middleware, domains, namespaces, and more
- **Multiple View Modes**: 
  - **List View**: Detailed tabular view with pagination and copy-to-clipboard functionality
  - **Graph View**: Interactive network visualization using Vis.js, D3.js, or Mermaid.js
  - **Tree View**: Hierarchical visualization organized by route structure
- **Route Validation**: Visual indicators for duplicate routes, missing controllers, and missing methods
- **Dark/Light Theme**: Toggle between themes with persistent preference
- **Export Options**: Download filtered route data in various formats
- **Telescope Integration**: Direct links to route performance data (when available)

### Artisan Commands

#### Install Package

Install and publish all package files:

```bash
php artisan route-visualizer:install
```

#### Export Routes

Export your route structure to static files with advanced filtering:

```bash
# Export to HTML (default)
php artisan route:export

# Export to JSON
php artisan route:export --format=json

# Export to SVG
php artisan route:export --format=svg

# Export with filters
php artisan route:export --filter-method=GET --filter-search=api

# Filter by middleware
php artisan route:export --filter-middleware=auth

# Filter by domain
php artisan route:export --filter-domain=api.example.com

# Filter by namespace
php artisan route:export --filter-namespace="App\Http\Controllers\Api"

# Filter by middleware group
php artisan route:export --filter-middleware-group=web

# Specify custom output path
php artisan route:export --output=/path/to/routes.html

# Use custom template
php artisan route:export --template=minimal
```

### Programmatic Usage

You can also use the route scanner programmatically:

```php
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;

class YourController extends Controller
{
    public function analyzeRoutes(RouteScanner $scanner)
    {
        // Get all routes with validation
        $routes = $scanner->scanRoutes();
        
        // Get routes grouped by controller
        $byController = $scanner->getRoutesGroupedByController();
        
        // Get routes grouped by middleware
        $byMiddleware = $scanner->getRoutesGroupedByMiddleware();
        
        // Get routes grouped by domain
        $byDomain = $scanner->getRoutesGroupedByDomain();
        
        // Get routes grouped by namespace
        $byNamespace = $scanner->getRoutesGroupedByNamespace();
        
        // Find duplicate routes
        $duplicates = $scanner->findDuplicateRoutes();
        
        // Get hierarchical tree data
        $treeData = $scanner->getTreeData();
        
        // Clear cache
        $scanner->clearCache();
        
        return response()->json($routes);
    }
}
```

## API Endpoints

The package provides several API endpoints:

- `GET /route-visualizer/routes` - Get filtered route data with pagination
- `GET /route-visualizer/graph` - Get network graph data (supports `?type=vis|d3|mermaid`)
- `GET /route-visualizer/tree-data` - Get hierarchical tree data
- `POST /route-visualizer/clear-cache` - Clear route cache

### Query Parameters

Filter routes using query parameters:

```
GET /route-visualizer/routes?search=api&method=POST&middleware=auth&domain=api.example.com&namespace=App\Http\Controllers\Api&middleware_group=web&page=1&per_page=50
```

## Customization

### Custom Views

After publishing views, you can customize the dashboard:

```bash
php artisan vendor:publish --tag=route-visualizer-views
```

Edit the views in `resources/views/vendor/route-visualizer/`.

### Custom Styling

The dashboard includes comprehensive dark/light theme support. You can customize colors and styling by:

1. Publishing the views and modifying the CSS
2. Overriding the Tailwind classes in your custom views
3. Adding custom CSS to your application's stylesheets

### Custom Export Templates

Create custom export templates by extending the base export view:

```php
// In your AppServiceProvider
View::composer('route-visualizer::export.html', function ($view) {
    $view->with('customData', 'Your custom data');
});
```

### Visualization Libraries

Choose your preferred visualization library in the configuration:

- **Vis.js**: Best for interactive network graphs with good performance
- **D3.js**: Most flexible with custom layouts and advanced interactions
- **Mermaid.js**: Simple, text-based diagrams that are easy to understand

## Security Considerations

### Production Usage

The visualizer is automatically disabled in production environments. To enable it:

```env
ROUTE_VISUALIZER_ENABLED=true
```

### Sensitive Routes

Configure patterns to hide sensitive routes:

```php
'security' => [
    'hide_sensitive_routes' => true,
    'sensitive_patterns' => [
        'admin/*',
        'api/admin/*',
        '*/password/*',
        'telescope/*',
        'horizon/*',
        'nova/*',
    ],
],
```

### Access Control

Add authentication middleware:

```php
'middleware' => ['web', 'auth', 'admin'],
```

## Performance

### Caching

Enable caching for better performance with large route sets:

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 hour
],
```

### Pagination

The package includes built-in pagination and lazy loading:

```php
'visualization' => [
    'pagination' => [
        'enabled' => true,
        'per_page' => 50,
    ],
],
```

### Memory Usage

For applications with thousands of routes, consider:

- Enabling route caching
- Using pagination in the web interface
- Filtering routes at the scanner level
- Using the export functionality for static analysis

## Validation Features

The package automatically validates your routes and identifies:

- **Duplicate Routes**: Routes with identical URIs and HTTP methods
- **Missing Controllers**: Routes pointing to non-existent controller classes
- **Missing Methods**: Routes pointing to non-existent controller methods

Configure validation in your config file:

```php
'validation' => [
    'check_duplicates' => true,
    'check_missing_controllers' => true,
    'check_missing_methods' => true,
],
```

## Laravel Telescope Integration

When Laravel Telescope is installed, the visualizer automatically adds direct links to route performance data:

```php
'integrations' => [
    'telescope' => [
        'enabled' => class_exists(\Laravel\Telescope\TelescopeServiceProvider::class),
        'base_url' => '/telescope',
    ],
],
```

## Testing

Run the package tests:

```bash
vendor/bin/phpunit
```

The package includes comprehensive feature and unit tests covering:

- Route scanning and data extraction
- API endpoints and filtering
- Export functionality
- Validation features
- Graph data generation

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Changelog

### v2.0.0
- **NEW**: Multiple visualization libraries (Vis.js, D3.js, Mermaid.js)
- **NEW**: Dark/light theme support with persistent preferences
- **NEW**: Tree view for hierarchical route visualization
- **NEW**: Advanced filtering by domain, namespace, and middleware groups
- **NEW**: Route validation with duplicate detection and missing controller/method checks
- **NEW**: Copy to clipboard functionality for routes and controllers
- **NEW**: Pagination and lazy loading for improved performance
- **NEW**: Laravel Telescope integration with direct performance links
- **NEW**: Enhanced export capabilities with filter support
- **NEW**: Installation command for easy setup
- **IMPROVED**: Enhanced UI with better responsive design
- **IMPROVED**: More comprehensive statistics and analytics
- **IMPROVED**: Better error handling and user feedback

### v1.0.0
- Initial release
- Web dashboard with interactive visualization
- Export functionality
- Caching support
- Security features

## Support

- 📧 Email: support@example.com
- 🐛 Issues: [GitHub Issues](https://github.com/onamfc/laravel-route-visualizer/issues)
- 📖 Documentation: [GitHub Wiki](https://github.com/onamfc/laravel-route-visualizer/wiki)