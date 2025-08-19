<?php

namespace onamfc\LaravelRouteVisualizer\Services;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use ReflectionException;

class RouteScanner
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function scanRoutes(): Collection
    {
        if (config('route-visualizer.cache.enabled')) {
            return Cache::remember(
                config('route-visualizer.cache.key'),
                config('route-visualizer.cache.ttl'),
                fn() => $this->extractRouteData()
            );
        }

        return $this->extractRouteData();
    }

    protected function extractRouteData(): Collection
    {
        $routes = collect($this->router->getRoutes()->getRoutes());

        return $routes->map(function (Route $route) {
            return $this->formatRoute($route);
        })->filter(function ($route) {
            return $this->shouldIncludeRoute($route);
        });
    }

    protected function formatRoute(Route $route): array
    {
        $action = $route->getAction();
        $controller = $action['controller'] ?? null;
        $controllerInfo = $this->parseControllerAction($controller);

        $routeData = [
            'id' => md5($route->uri() . implode('|', $route->methods())),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'methods' => $route->methods(),
            'controller' => $controllerInfo,
            'middleware' => $this->getRouteMiddleware($route),
            'middleware_groups' => $this->getMiddlewareGroups($route),
            'parameters' => $route->parameterNames(),
            'where' => $route->wheres,
            'domain' => $route->domain(),
            'subdomain' => $this->getSubdomain($route),
            'prefix' => $this->getRoutePrefix($route),
            'namespace' => $action['namespace'] ?? null,
            'as' => $action['as'] ?? null,
            'uses' => $action['uses'] ?? null,
        ];

        // Add validation flags
        if (config('route-visualizer.validation.check_duplicates')) {
            $routeData['validation'] = $this->validateRoute($routeData);
        }

        return $routeData;
    }

    protected function validateRoute(array $route): array
    {
        $validation = [
            'is_duplicate' => false,
            'missing_controller' => false,
            'missing_method' => false,
            'warnings' => [],
        ];

        // Check for missing controller/method
        if ($route['controller'] && config('route-visualizer.validation.check_missing_controllers')) {
            $controllerClass = $route['controller']['class'];
            $method = $route['controller']['method'];

            try {
                if (!class_exists($controllerClass)) {
                    $validation['missing_controller'] = true;
                    $validation['warnings'][] = "Controller class '{$controllerClass}' not found";
                } else {
                    $reflection = new ReflectionClass($controllerClass);
                    if (!$reflection->hasMethod($method)) {
                        $validation['missing_method'] = true;
                        $validation['warnings'][] = "Method '{$method}' not found in controller";
                    }
                }
            } catch (ReflectionException $e) {
                $validation['missing_controller'] = true;
                $validation['warnings'][] = "Error checking controller: " . $e->getMessage();
            }
        }

        return $validation;
    }

    protected function getMiddlewareGroups(Route $route): array
    {
        $middleware = $this->getRouteMiddleware($route);
        $groups = [];

        foreach ($middleware as $middlewareName) {
            // Check if this middleware is actually a group
            $middlewareGroups = app('router')->getMiddlewareGroups();
            if (array_key_exists($middlewareName, $middlewareGroups)) {
                $groups[] = $middlewareName;
            }
        }

        return $groups;
    }

    protected function getSubdomain(Route $route): ?string
    {
        $domain = $route->domain();
        if (!$domain) {
            return null;
        }

        // Extract subdomain from domain pattern
        if (str_contains($domain, '{')) {
            // Dynamic subdomain like {subdomain}.example.com
            return $domain;
        }

        // Static subdomain
        $parts = explode('.', $domain);
        if (count($parts) > 2) {
            return $parts[0];
        }

        return null;
    }

    protected function parseControllerAction(?string $controller): ?array
    {
        if (!$controller) {
            return null;
        }

        if (str_contains($controller, '@')) {
            [$class, $method] = explode('@', $controller);
            return [
                'class' => $class,
                'method' => $method,
                'full' => $controller,
            ];
        }

        return [
            'class' => $controller,
            'method' => '__invoke',
            'full' => $controller,
        ];
    }

    protected function getRouteMiddleware(Route $route): array
    {
        $middleware = [];

        // Get middleware from route action
        $actionMiddleware = $route->getAction('middleware') ?? [];
        if (is_string($actionMiddleware)) {
            $actionMiddleware = [$actionMiddleware];
        }

        // Get middleware from route directly
        $routeMiddleware = $route->middleware();

        return array_unique(array_merge($actionMiddleware, $routeMiddleware));
    }

    protected function getRoutePrefix(Route $route): ?string
    {
        $action = $route->getAction();
        return $action['prefix'] ?? null;
    }

    protected function shouldIncludeRoute(array $route): bool
    {
        if (!config('route-visualizer.security.hide_sensitive_routes')) {
            return true;
        }

        $patterns = config('route-visualizer.security.sensitive_patterns', []);

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $route['uri'])) {
                return false;
            }
        }

        return true;
    }

    public function clearCache(): void
    {
        Cache::forget(config('route-visualizer.cache.key'));
    }

    public function getRoutesGroupedByController(): Collection
    {
        return $this->scanRoutes()->groupBy('controller.class');
    }

    public function getRoutesGroupedByPrefix(): Collection
    {
        return $this->scanRoutes()->groupBy('prefix');
    }

    public function getRoutesGroupedByMiddleware(): Collection
    {
        $routes = $this->scanRoutes();
        $grouped = collect();

        $routes->each(function ($route) use ($grouped) {
            foreach ($route['middleware'] as $middleware) {
                if (!$grouped->has($middleware)) {
                    $grouped->put($middleware, collect());
                }
                $grouped->get($middleware)->push($route);
            }
        });

        return $grouped;
    }

    public function getRoutesGroupedByDomain(): Collection
    {
        return $this->scanRoutes()->groupBy('domain');
    }

    public function getRoutesGroupedByNamespace(): Collection
    {
        return $this->scanRoutes()->groupBy('namespace');
    }

    public function getRoutesGroupedByMiddlewareGroup(): Collection
    {
        $routes = $this->scanRoutes();
        $grouped = collect();

        $routes->each(function ($route) use ($grouped) {
            foreach ($route['middleware_groups'] as $group) {
                if (!$grouped->has($group)) {
                    $grouped->put($group, collect());
                }
                $grouped->get($group)->push($route);
            }
        });

        return $grouped;
    }

    public function findDuplicateRoutes(): Collection
    {
        $routes = $this->scanRoutes();
        $duplicates = collect();

        // Group by URI and methods combination
        $grouped = $routes->groupBy(function ($route) {
            return $route['uri'] . '|' . implode(',', $route['methods']);
        });

        $grouped->each(function ($routeGroup, $key) use ($duplicates) {
            if ($routeGroup->count() > 1) {
                $duplicates->put($key, $routeGroup);
            }
        });

        return $duplicates;
    }

    public function getTreeData(): array
    {
        $routes = $this->scanRoutes();
        $tree = [];

        foreach ($routes as $route) {
            $parts = explode('/', trim($route['uri'], '/'));
            $current = &$tree;

            foreach ($parts as $part) {
                if (!isset($current[$part])) {
                    $current[$part] = [
                        'name' => $part,
                        'routes' => [],
                        'children' => [],
                    ];
                }
                $current = &$current[$part]['children'];
            }

            // Add the route to the final segment
            $finalPart = end($parts) ?: '/';
            if (!isset($tree[$finalPart])) {
                $tree[$finalPart] = [
                    'name' => $finalPart,
                    'routes' => [],
                    'children' => [],
                ];
            }
            $tree[$finalPart]['routes'][] = $route;
        }

        return $tree;
    }
}