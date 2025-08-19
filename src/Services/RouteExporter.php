<?php

namespace onamfc\LaravelRouteVisualizer\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class RouteExporter
{
    public function export(Collection $routes, string $format, ?string $output = null, string $template = 'default', array $filters = []): string
    {
        $exportPath = $output ?: $this->getDefaultExportPath($format);
        
        match ($format) {
            'html' => $this->exportHtml($routes, $exportPath, $template, $filters),
            'json' => $this->exportJson($routes, $exportPath),
            'svg' => $this->exportSvg($routes, $exportPath),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };

        return $exportPath;
    }

    protected function exportHtml(Collection $routes, string $path, string $template, array $filters = []): void
    {
        $html = $this->generateHtmlContent($routes, $template, $filters);
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $html);
    }

    protected function exportJson(Collection $routes, string $path): void
    {
        $json = json_encode([
            'routes' => $routes->toArray(),
            'exported_at' => now()->toISOString(),
            'total' => $routes->count(),
        ], JSON_PRETTY_PRINT);
        
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $json);
    }

    protected function exportSvg(Collection $routes, string $path): void
    {
        // Generate SVG representation of routes
        $svg = $this->generateSvgContent($routes);
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $svg);
    }

    protected function generateHtmlContent(Collection $routes, string $template, array $filters = []): string
    {
        $controllers = $routes->groupBy('controller.class');
        $middleware = $routes->pluck('middleware')->flatten()->unique()->sort();
        $middlewareGroups = $routes->pluck('middleware_groups')->flatten()->unique()->sort();
        $domains = $routes->pluck('domain')->filter()->unique()->sort();
        $namespaces = $routes->pluck('namespace')->filter()->unique()->sort();
        $methods = $routes->pluck('methods')->flatten()->unique()->sort();

        return view('route-visualizer::export.html', compact(
            'routes', 
            'controllers', 
            'middleware', 
            'middlewareGroups',
            'domains',
            'namespaces',
            'methods',
            'template',
            'filters'
        ))->render();
    }

    protected function generateSvgContent(Collection $routes): string
    {
        // Basic SVG generation - can be enhanced with more sophisticated layouts
        $width = 800;
        $height = max(600, $routes->count() * 30 + 100);
        
        $svg = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $svg .= "<svg width=\"{$width}\" height=\"{$height}\" xmlns=\"http://www.w3.org/2000/svg\">\n";
        $svg .= "<style>\n";
        $svg .= ".route-text { font-family: Arial, sans-serif; font-size: 12px; }\n";
        $svg .= ".method-get { fill: #10b981; }\n";
        $svg .= ".method-post { fill: #3b82f6; }\n";
        $svg .= ".method-put { fill: #f59e0b; }\n";
        $svg .= ".method-delete { fill: #ef4444; }\n";
        $svg .= "</style>\n";
        
        $y = 30;
        foreach ($routes as $route) {
            $method = $route['methods'][0] ?? 'GET';
            $methodClass = 'method-' . strtolower($method);
            
            $svg .= "<circle cx=\"20\" cy=\"{$y}\" r=\"5\" class=\"{$methodClass}\" />\n";
            $svg .= "<text x=\"35\" y=\"" . ($y + 4) . "\" class=\"route-text\">";
            $svg .= htmlspecialchars("{$method} {$route['uri']}");
            $svg .= "</text>\n";
            
            $y += 25;
        }
        
        $svg .= "</svg>\n";
        
        return $svg;
    }

    protected function getDefaultExportPath(string $format): string
    {
        $basePath = config('route-visualizer.export.path');
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return "{$basePath}/routes_{$timestamp}.{$format}";
    }

    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}