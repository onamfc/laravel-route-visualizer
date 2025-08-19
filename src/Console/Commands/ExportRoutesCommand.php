<?php

namespace onamfc\LaravelRouteVisualizer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;

class ExportRoutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'route:export 
                            {--format=html : Export format (html, json, svg)}
                            {--output= : Output file path}
                            {--template=default : Template to use (default, minimal, detailed)}
                            {--filter-method= : Filter by HTTP method}
                            {--filter-middleware= : Filter by middleware}
                            {--filter-domain= : Filter by domain}
                            {--filter-namespace= : Filter by namespace}
                            {--filter-middleware-group= : Filter by middleware group}
                            {--filter-search= : Search filter}';

    /**
     * The console command description.
     */
    protected $description = 'Export Laravel routes to various formats';

    protected RouteScanner $scanner;

    public function __construct(RouteScanner $scanner)
    {
        parent::__construct();
        $this->scanner = $scanner;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $format = $this->option('format');
        $template = $this->option('template');
        
        if (!in_array($format, ['html', 'json', 'svg'])) {
            $this->error('Invalid format. Supported formats: html, json, svg');
            return self::FAILURE;
        }

        $this->info("Exporting routes in {$format} format...");

        // Scan routes
        $routes = $this->scanner->scanRoutes();
        
        // Apply filters
        $routes = $this->applyFilters($routes);
        
        // Get additional data
        $controllers = $routes->pluck('controller.class')->filter()->unique();
        $middleware = $routes->pluck('middleware')->flatten()->unique();
        $methods = $routes->pluck('methods')->flatten()->unique();
        $domains = $routes->pluck('domain')->filter()->unique();
        $namespaces = $routes->pluck('namespace')->filter()->unique();
        
        $data = [
            'routes' => $routes,
            'controllers' => $controllers,
            'middleware' => $middleware,
            'methods' => $methods,
            'domains' => $domains,
            'namespaces' => $namespaces,
            'filters' => $this->getAppliedFilters(),
            'exported_at' => now(),
        ];

        // Generate output
        $output = $this->generateOutput($data, $format, $template);
        
        // Save to file
        $filePath = $this->getOutputPath($format);
        $this->ensureDirectoryExists($filePath);
        
        File::put($filePath, $output);
        
        $this->info("✓ Routes exported to: {$filePath}");
        $this->line("Total routes exported: {$routes->count()}");
        
        return self::SUCCESS;
    }

    /**
     * Apply command line filters to routes
     */
    protected function applyFilters($routes)
    {
        if ($method = $this->option('filter-method')) {
            $routes = $routes->filter(fn($route) => in_array($method, $route['methods']));
        }

        if ($middleware = $this->option('filter-middleware')) {
            $routes = $routes->filter(fn($route) => in_array($middleware, $route['middleware']));
        }

        if ($domain = $this->option('filter-domain')) {
            $routes = $routes->filter(fn($route) => $route['domain'] === $domain);
        }

        if ($namespace = $this->option('filter-namespace')) {
            $routes = $routes->filter(fn($route) => $route['namespace'] === $namespace);
        }

        if ($search = $this->option('filter-search')) {
            $routes = $routes->filter(function ($route) use ($search) {
                return str_contains(strtolower($route['uri']), strtolower($search)) ||
                       str_contains(strtolower($route['name'] ?? ''), strtolower($search)) ||
                       str_contains(strtolower($route['controller']['class'] ?? ''), strtolower($search));
            });
        }

        return $routes;
    }

    /**
     * Get applied filters for display
     */
    protected function getAppliedFilters(): array
    {
        $filters = [];
        
        if ($method = $this->option('filter-method')) {
            $filters['method'] = $method;
        }
        
        if ($middleware = $this->option('filter-middleware')) {
            $filters['middleware'] = $middleware;
        }
        
        if ($domain = $this->option('filter-domain')) {
            $filters['domain'] = $domain;
        }
        
        if ($namespace = $this->option('filter-namespace')) {
            $filters['namespace'] = $namespace;
        }
        
        if ($search = $this->option('filter-search')) {
            $filters['search'] = $search;
        }

        return $filters;
    }

    /**
     * Generate output in specified format
     */
    protected function generateOutput(array $data, string $format, string $template): string
    {
        switch ($format) {
            case 'html':
                return View::make('route-visualizer::export.html', $data)->render();
                
            case 'json':
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                
            case 'svg':
                // For SVG, we'll create a simple text-based representation
                return $this->generateSvgOutput($data);
                
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    /**
     * Generate SVG output
     */
    protected function generateSvgOutput(array $data): string
    {
        $routes = $data['routes'];
        $height = max(400, $routes->count() * 20 + 100);
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $svg .= '<svg width="800" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">' . "\n";
        $svg .= '<style>text { font-family: monospace; font-size: 12px; }</style>' . "\n";
        
        $y = 30;
        foreach ($routes as $route) {
            $methods = implode('|', $route['methods']);
            $svg .= '<text x="10" y="' . $y . '" fill="#333">' . htmlspecialchars($methods . ' ' . $route['uri']) . '</text>' . "\n";
            $y += 20;
        }
        
        $svg .= '</svg>';
        
        return $svg;
    }

    /**
     * Get output file path
     */
    protected function getOutputPath(string $format): string
    {
        if ($output = $this->option('output')) {
            return $output;
        }

        $exportPath = config('route-visualizer.export.path', storage_path('app/route-maps'));
        $filename = 'routes-' . date('Y-m-d-H-i-s') . '.' . $format;
        
        return $exportPath . '/' . $filename;
    }

    /**
     * Ensure directory exists
     */
    protected function ensureDirectoryExists(string $filePath): void
    {
        $directory = dirname($filePath);
        
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}