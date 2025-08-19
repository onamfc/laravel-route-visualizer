<?php

namespace onamfc\LaravelRouteVisualizer\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;

class RouteScanner
{
    protected Collection $routes;
    protected array $duplicates = [];
    protected array $validationIssues = [];

    public function __construct()
    {
        $this->routes = collect();
    }

    /**
     * Scan all routes and return formatted data
     */
    public function scanRoutes(): Collection
    {
        $cacheKey = config('route-visualizer.cache.key', 'route_visualizer_data');
        $cacheTtl = config('route-visualizer.cache.ttl', 3600);

        if (config('route-visualizer.cache.enabled', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->scanRoutesCollection();
            });
        }

        return $this->scanRoutesCollection();
    }

    /**
     * Scan routes collection without caching
     */
    public function scanRoutesCollection(): Collection
    {
        $routes = collect();
        $routeCollection = RouteFacade::getRoutes();

        foreach ($routeCollection as $route) {
            if ($this->shouldSkipRoute($route)) {
                continue;
            }

            $routeData = $this->extractRouteData($route);
            $routeData['validation'] = $this->validateRoute($route, $routeData);
            
            $routes->push($routeData);
        }

        $this->routes = $routes;
        $this->findDuplicateRoutes();

        return $routes;
    }

    /**
     * Extract data from a single route
     */
    protected function extractRouteData(Route $route): array
    {
        $action = $route->getAction();
        $controller = $this->parseControllerAction($action);

        return [
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'methods' => $route->methods(),
            'middleware' => $this->getRouteMiddleware($route),
            'controller' => $controller,
            'parameters' => $route->parameterNames(),
            'domain' => $route->getDomain(),
            'namespace' => $action['namespace'] ?? null,
            'prefix' => $action['prefix'] ?? null,
            'where' => $route->wheres,
            'compiled' => $route->getCompiled()?->getPattern(),
        ];
    }

    /**
     * Parse controller action from route action
     */
    protected function parseControllerAction(array $action): ?array
    {
        if (!isset($action['controller'])) {
            return null;
        }

        $controller = $action['controller'];

        if (is_string($controller)) {
            if (Str::contains($controller, '@')) {
                [$class, $method] = explode('@', $controller, 2);
                return [
                    'class' => $class,
                    'method' => $method,
                    'full' => $controller,
                ];
            }
        }

        if (is_array($controller) && count($controller) === 2) {
            return [
                'class' => is_object($controller[0]) ? get_class($controller[0]) : $controller[0],
                'method' => $controller[1],
                'full' => (is_object($controller[0]) ? get_class($controller[0]) : $controller[0]) . '@' . $controller[1],
            ];
        }

        return null;
    }

    /**
     * Get middleware for a route
     */
    protected function getRouteMiddleware(Route $route): array
    {
        $middleware = [];
        
        foreach ($route->gatherMiddleware() as $middlewareItem) {
            if (is_string($middlewareItem)) {
                $middleware[] = $middlewareItem;
            } elseif (is_object($middlewareItem)) {
                $middleware[] = get_class($middlewareItem);
            }
        }

        return array_unique($middleware);
    }

    /**
     * Validate a route for common issues
     */
    protected function validateRoute(Route $route, array $routeData): array
    {
        $validation = [
            'is_duplicate' => false,
            'missing_controller' => false,
            'missing_method' => false,
            'warnings' => [],
        ];

        if (!config('route-visualizer.validation.check_missing_controllers', true)) {
            return $validation;
        }

        $controller = $routeData['controller'];
        if ($controller) {
            // Check if controller class exists
            if (!class_exists($controller['class'])) {
                $validation['missing_controller'] = true;
                $validation['warnings'][] = "Controller class '{$controller['class']}' not found";
            } else {
                // Check if method exists
                try {
                    $reflection = new ReflectionClass($controller['class']);
                    if (!$reflection->hasMethod($controller['method'])) {
                        $validation['missing_method'] = true;
                        $validation['warnings'][] = "Method '{$controller['method']}' not found in controller '{$controller['class']}'";
                    }
                } catch (ReflectionException $e) {
                    $validation['warnings'][] = "Could not reflect controller class: " . $e->getMessage();
                }
            }
        }

        return $validation;
    }

    /**
     * Check if route should be skipped based on security settings
     */
    protected function shouldSkipRoute(Route $route): bool
    {
        if (!config('route-visualizer.security.hide_sensitive_routes', true)) {
            return false;
        }

        $sensitivePatterns = config('route-visualizer.security.sensitive_patterns', []);
        $uri = $route->uri();

        foreach ($sensitivePatterns as $pattern) {
            if (Str::is($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find duplicate routes
     */
    public function findDuplicateRoutes(): Collection
    {
        if (!config('route-visualizer.validation.check_duplicates', true)) {
            return collect();
        }

        $duplicates = collect();
        $routeSignatures = [];

        foreach ($this->routes as $index => $route) {
            $signature = implode('|', $route['methods']) . ':' . $route['uri'];
            
            if (isset($routeSignatures[$signature])) {
                $duplicates->push([
                    'signature' => $signature,
                    'routes' => [$routeSignatures[$signature], $index],
                ]);
                
                // Mark both routes as duplicates
                $this->routes[$routeSignatures[$signature]]['validation']['is_duplicate'] = true;
                $this->routes[$index]['validation']['is_duplicate'] = true;
            } else {
                $routeSignatures[$signature] = $index;
            }
        }

        return $duplicates;
    }

    /**
     * Group routes by controller
     */
    public function getRoutesGroupedByController(): Collection
    {
        return $this->routes->groupBy(function ($route) {
            return $route['controller']['class'] ?? 'Closure';
        });
    }

    /**
     * Group routes by middleware
     */
    public function getRoutesGroupedByMiddleware(): Collection
    {
        $grouped = collect();

        foreach ($this->routes as $route) {
            foreach ($route['middleware'] as $middleware) {
                if (!$grouped->has($middleware)) {
                    $grouped->put($middleware, collect());
                }
                $grouped->get($middleware)->push($route);
            }
        }

        return $grouped;
    }

    /**
     * Group routes by domain
     */
    public function getRoutesGroupedByDomain(): Collection
    {
        return $this->routes->groupBy('domain');
    }

    /**
     * Group routes by namespace
     */
    public function getRoutesGroupedByNamespace(): Collection
    {
        return $this->routes->groupBy('namespace');
    }

    /**
     * Group routes by prefix
     */
    public function getRoutesGroupedByPrefix(): Collection
    {
        return $this->routes->groupBy('prefix');
    }

    /**
     * Get tree data for hierarchical visualization
     */
    public function getTreeData(): array
    {
        $tree = [];

        foreach ($this->routes as $route) {
            $segments = explode('/', trim($route['uri'], '/'));
            $currentLevel = &$tree;

            foreach ($segments as $segment) {
                if (!isset($currentLevel[$segment])) {
                    $currentLevel[$segment] = [
                        'name' => $segment,
                        'routes' => [],
                        'children' => [],
                    ];
                }
                $currentLevel = &$currentLevel[$segment]['children'];
            }

            // Add route to the final segment
            $finalSegment = end($segments) ?: 'root';
            if (!isset($tree[$finalSegment])) {
                $tree[$finalSegment] = [
                    'name' => $finalSegment,
                    'routes' => [],
                    'children' => [],
                ];
            }
            $tree[$finalSegment]['routes'][] = $route;
        }

        return ['tree' => $tree];
    }

    /**
     * Clear route cache
     */
    public function clearCache(): void
    {
        $cacheKey = config('route-visualizer.cache.key', 'route_visualizer_data');
        Cache::forget($cacheKey);
    }

    /**
     * Get statistics about routes
     */
    public function getStatistics(): array
    {
        $routes = $this->routes;

        return [
            'total_routes' => $routes->count(),
            'total_controllers' => $routes->pluck('controller.class')->filter()->unique()->count(),
            'total_middleware' => $routes->pluck('middleware')->flatten()->unique()->count(),
            'total_methods' => $routes->pluck('methods')->flatten()->unique()->count(),
            'total_domains' => $routes->pluck('domain')->filter()->unique()->count(),
            'total_namespaces' => $routes->pluck('namespace')->filter()->unique()->count(),
            'total_prefixes' => $routes->pluck('prefix')->filter()->unique()->count(),
            'validation_issues' => $routes->filter(function ($route) {
                return $route['validation']['missing_controller'] || 
                       $route['validation']['missing_method'] || 
                       $route['validation']['is_duplicate'];
            })->count(),
        ];
    }
}