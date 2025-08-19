<?php

namespace onamfc\LaravelRouteVisualizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;

class RouteVisualizerController
{
    protected RouteScanner $routeScanner;

    public function __construct(RouteScanner $routeScanner)
    {
        $this->routeScanner = $routeScanner;
    }

    public function index(): View
    {
        return view('route-visualizer::dashboard', [
            'config' => config('route-visualizer'),
        ]);
    }

    public function routes(Request $request): JsonResponse
    {
        $routes = $this->routeScanner->scanRoutes();
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', config('route-visualizer.visualization.pagination.per_page', 50));

        if ($request->has('search')) {
            $search = $request->get('search');
            $routes = $routes->filter(function ($route) use ($search) {
                return str_contains($route['uri'], $search) ||
                    str_contains($route['name'] ?? '', $search) ||
                    str_contains($route['controller']['class'] ?? '', $search) ||
                    str_contains($route['namespace'] ?? '', $search);
            });
        }

        if ($request->has('method')) {
            $method = strtoupper($request->get('method'));
            $routes = $routes->filter(function ($route) use ($method) {
                return in_array($method, $route['methods']);
            });
        }

        if ($request->has('middleware')) {
            $middleware = $request->get('middleware');
            $routes = $routes->filter(function ($route) use ($middleware) {
                return in_array($middleware, $route['middleware']);
            });
        }

        if ($request->has('middleware_group')) {
            $middlewareGroup = $request->get('middleware_group');
            $routes = $routes->filter(function ($route) use ($middlewareGroup) {
                return in_array($middlewareGroup, $route['middleware_groups']);
            });
        }

        if ($request->has('domain')) {
            $domain = $request->get('domain');
            $routes = $routes->filter(function ($route) use ($domain) {
                return $route['domain'] === $domain;
            });
        }

        if ($request->has('namespace')) {
            $namespace = $request->get('namespace');
            $routes = $routes->filter(function ($route) use ($namespace) {
                return $route['namespace'] === $namespace;
            });
        }

        // Pagination
        $total = $routes->count();
        $paginatedRoutes = $routes->forPage($page, $perPage);
        return response()->json([
            'routes' => $paginatedRoutes->values(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total,
            'grouped' => [
                'controllers' => $this->routeScanner->getRoutesGroupedByController()->map->count(),
                'prefixes' => $this->routeScanner->getRoutesGroupedByPrefix()->map->count(),
                'middleware' => $this->routeScanner->getRoutesGroupedByMiddleware()->map->count(),
                'middleware_groups' => $this->routeScanner->getRoutesGroupedByMiddlewareGroup()->map->count(),
                'domains' => $this->routeScanner->getRoutesGroupedByDomain()->map->count(),
                'namespaces' => $this->routeScanner->getRoutesGroupedByNamespace()->map->count(),
            ],
            'duplicates' => $this->routeScanner->findDuplicateRoutes()->count(),
        ]);
    }

    public function graph(): JsonResponse
    {
        $routes = $this->getFilteredRoutes(request());

        return $this->getVisGraphData($routes);
    }

    protected function getVisGraphData(Collection $routes): JsonResponse
    {
        $nodes = collect();
        $edges = collect();

        // Create controller nodes
        $controllers = $routes->groupBy('controller.class')->keys();
        $controllers->each(function ($controller, $index) use ($nodes) {
            if ($controller) {
                $nodes->push([
                    'id' => "controller_{$index}",
                    'label' => class_basename($controller),
                    'group' => 'controller',
                    'title' => $controller,
                ]);
            }
        });

        // Create route nodes and edges
        $routes->each(function ($route, $index) use ($nodes, $edges, $controllers) {
            $routeId = "route_{$index}";

            $color = '#10B981'; // default green
            if (isset($route['validation'])) {
                if ($route['validation']['missing_controller'] || $route['validation']['missing_method']) {
                    $color = '#EF4444'; // red for errors
                } elseif ($route['validation']['is_duplicate']) {
                    $color = '#F59E0B'; // yellow for warnings
                }
            }

            $nodes->push([
                'id' => $routeId,
                'label' => $route['uri'],
                'group' => 'route',
                'title' => implode('|', $route['methods']) . ' ' . $route['uri'],
                'methods' => $route['methods'],
                'color' => $color,
            ]);

            // Connect to controller
            if ($route['controller']['class'] ?? null) {
                $controllerIndex = $controllers->search($route['controller']['class']);
                if ($controllerIndex !== false) {
                    $edges->push([
                        'from' => "controller_{$controllerIndex}",
                        'to' => $routeId,
                        'label' => $route['controller']['method'] ?? '',
                    ]);
                }
            }
        });

        return response()->json([
            'nodes' => $nodes->values(),
            'edges' => $edges->values(),
        ]);
    }

    public function treeData(): JsonResponse
    {
        $routes = $this->getFilteredRoutes(request());
        $treeData = $this->getTreeDataFromRoutes($routes);

        return response()->json([
            'tree' => $treeData,
        ]);
    }

    public function clearCache(): JsonResponse
    {
        $this->routeScanner->clearCache();

        return response()->json([
            'message' => 'Route cache cleared successfully',
        ]);
    }

    protected function getFilteredRoutes(Request $request): Collection
    {
        $routes = $this->routeScanner->scanRoutes();

        if ($request->has('search')) {
            $search = $request->get('search');
            $routes = $routes->filter(function ($route) use ($search) {
                return str_contains($route['uri'], $search) ||
                    str_contains($route['name'] ?? '', $search) ||
                    str_contains($route['controller']['class'] ?? '', $search) ||
                    str_contains($route['namespace'] ?? '', $search);
            });
        }

        if ($request->has('method')) {
            $method = strtoupper($request->get('method'));
            $routes = $routes->filter(function ($route) use ($method) {
                return in_array($method, $route['methods']);
            });
        }

        if ($request->has('middleware')) {
            $middleware = $request->get('middleware');
            $routes = $routes->filter(function ($route) use ($middleware) {
                return in_array($middleware, $route['middleware']);
            });
        }

        if ($request->has('middleware_group')) {
            $middlewareGroup = $request->get('middleware_group');
            $routes = $routes->filter(function ($route) use ($middlewareGroup) {
                return in_array($middlewareGroup, $route['middleware_groups']);
            });
        }

        if ($request->has('domain')) {
            $domain = $request->get('domain');
            $routes = $routes->filter(function ($route) use ($domain) {
                return $route['domain'] === $domain;
            });
        }

        if ($request->has('namespace')) {
            $namespace = $request->get('namespace');
            $routes = $routes->filter(function ($route) use ($namespace) {
                return $route['namespace'] === $namespace;
            });
        }

        return $routes;
    }

    protected function getTreeDataFromRoutes(Collection $routes): array
    {
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