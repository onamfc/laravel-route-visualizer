<?php

namespace onamfc\LaravelRouteVisualizer\Console\Commands;

use Illuminate\Console\Command;
use onamfc\LaravelRouteVisualizer\Services\RouteExporter;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;

class ExportRoutesCommand extends Command
{
    protected $signature = 'route:export 
                           {--format=html : Export format (html, json, svg)}
                           {--output= : Output file path}
                           {--template=default : Template to use for HTML export}
                           {--filter-method= : Filter by HTTP method}
                           {--filter-search= : Filter by search term}
                           {--filter-middleware= : Filter by middleware}
                           {--filter-domain= : Filter by domain}
                           {--filter-namespace= : Filter by namespace}
                           {--filter-middleware-group= : Filter by middleware group}';

    protected $description = 'Export route visualization to static files';

    protected RouteScanner $routeScanner;
    protected RouteExporter $routeExporter;

    public function __construct(RouteScanner $routeScanner, RouteExporter $routeExporter)
    {
        parent::__construct();
        $this->routeScanner = $routeScanner;
        $this->routeExporter = $routeExporter;
    }

    public function handle(): int
    {
        $format = $this->option('format');
        $output = $this->option('output');
        $template = $this->option('template');

        // Collect filters
        $filters = array_filter([
            'method' => $this->option('filter-method'),
            'search' => $this->option('filter-search'),
            'middleware' => $this->option('filter-middleware'),
            'domain' => $this->option('filter-domain'),
            'namespace' => $this->option('filter-namespace'),
            'middleware_group' => $this->option('filter-middleware-group'),
        ]);

        $this->info("Starting route export in {$format} format...");

        if (!empty($filters)) {
            $this->info("Applying filters: " . json_encode($filters));
        }

        try {
            $routes = $this->routeScanner->scanRoutes();

            // Apply filters
            $routes = $this->applyFilters($routes, $filters);

            $exportPath = $this->routeExporter->export($routes, $format, $output, $template, $filters);

            $this->info("✅ {$routes->count()} routes exported successfully to: {$exportPath}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Export failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function applyFilters(Collection $routes, array $filters): Collection
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            $routes = match ($key) {
                'method' => $routes->filter(fn($route) => in_array(strtoupper($value), $route['methods'])),
                'search' => $routes->filter(fn($route) =>
                    str_contains($route['uri'], $value) ||
                    str_contains($route['name'] ?? '', $value) ||
                    str_contains($route['controller']['class'] ?? '', $value)
                ),
                'middleware' => $routes->filter(fn($route) => in_array($value, $route['middleware'])),
                'domain' => $routes->filter(fn($route) => $route['domain'] === $value),
                'namespace' => $routes->filter(fn($route) => $route['namespace'] === $value),
                'middleware_group' => $routes->filter(fn($route) => in_array($value, $route['middleware_groups'])),
                default => $routes,
            };
        }
        return $routes;
    }
}