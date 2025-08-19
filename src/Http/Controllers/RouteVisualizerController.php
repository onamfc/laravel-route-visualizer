<?php

namespace onamfc\LaravelRouteVisualizer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;

class RouteVisualizerController extends Controller
{
    protected RouteScanner $scanner;

    public function __construct(RouteScanner $scanner)
    {
        $this->scanner = $scanner;
    }

    /**
     * Show the route visualizer dashboard
     */
    public function index()
    {
        return view('route-visualizer::dashboard');
    }

    /**
     * Get routes data with filtering and pagination
     */
    public function routes(Request $request): JsonResponse
    {
        $routes = $this->scanner->scanRoutes();
        
        // Apply filters
        $routes = $this->applyFilters($routes, $request);
        
        // Get statistics and groupings
        $grouped = [
            'controllers' => $this->scanner->getRoutesGroupedByController()->mapWithKeys(function ($routes, $controller) {
                return [$controller => $routes->count()];
            }),
            'middleware' => $this->scanner->getRoutesGroupedByMiddleware()->mapWithKeys(function ($routes, $middleware) {
                return [$middleware => $routes->count()];
            }),
            'domains' => $this->scanner->getRoutesGroupedByDomain()->mapWithKeys(function ($routes, $domain) {
                return [$domain ?: 'default' => $routes->count()];
            }),
            'namespaces' => $this->scanner->getRoutesGroupedByNamespace()->mapWithKeys(function ($routes, $namespace) {
                return [$namespace ?: 'default' => $routes->count()];
            }),
            'prefixes' => $this->scanner->getRoutesGroupedByPrefix()->mapWithKeys(function ($routes, $prefix) {
                return [$prefix ?: 'root' => $routes->count()];
            }),
        ];

        // Pagination
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 50);
        $total = $routes->count();
        
        $paginatedRoutes = $routes->forPage($page, $perPage)->values();
        
        return response()->json([
            'routes' => $paginatedRoutes,
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'has_more' => ($page * $perPage) < $total,
            'grouped' => $grouped,
            'duplicates' => $this->scanner->findDuplicateRoutes()->count(),
        ]);
    }

    /**
     * Get graph data for network visualization
     */
    public function graph(Request $request): JsonResponse
    {
        $routes = $this->scanner->scanRoutes();
        $routes = $this->applyFilters($routes, $request);

        $nodes = collect();
        $edges = collect();
        $nodeIds = [];

        // Create controller nodes
        $controllerGroups = $routes->groupBy('controller.class');
        foreach ($controllerGroups as $controller => $controllerRoutes) {
            if ($controller && $controller !== 'null') {
                $nodeId = 'controller_' . md5($controller);
                $nodeIds[$controller] = $nodeId;
                
                $hasIssues = $controllerRoutes->some(function ($route) {
                    return $route['validation']['missing_controller'] || $route['validation']['missing_method'];
                });
                
                $nodes->push([
                    'id' => $nodeId,
                    'label' => class_basename($controller),
                    'title' => $controller . "\n" . $controllerRoutes->count() . ' routes',
                    'group' => 'controller',
                    'color' => $hasIssues ? '#ef4444' : '#3b82f6',
                ]);
            }
        }

        // Create route nodes and edges
        foreach ($routes as $index => $route) {
            $routeId = 'route_' . $index;
            
            $color = '#10b981'; // Default green
            if ($route['validation']['is_duplicate']) {
                $color = '#f59e0b'; // Yellow for duplicates
            } elseif ($route['validation']['missing_controller'] || $route['validation']['missing_method']) {
                $color = '#ef4444'; // Red for errors
            }
            
            $nodes->push([
                'id' => $routeId,
                'label' => $route['uri'],
                'title' => implode('|', $route['methods']) . ' ' . $route['uri'] . 
                          ($route['name'] ? "\nName: " . $route['name'] : ''),
                'group' => 'route',
                'color' => $color,
            ]);

            // Create edge to controller if exists
            if ($route['controller'] && isset($nodeIds[$route['controller']['class']])) {
                $edges->push([
                    'from' => $nodeIds[$route['controller']['class']],
                    'to' => $routeId,
                    'arrows' => 'to',
                ]);
            }
        }

        return response()->json([
            'nodes' => $nodes->values(),
            'edges' => $edges->values(),
        ]);
    }

    /**
     * Get tree data for hierarchical visualization
     */
    public function treeData(Request $request): JsonResponse
    {
        $routes = $this->scanner->scanRoutes();
        $routes = $this->applyFilters($routes, $request);
        
        // Update scanner routes for tree generation
        $this->scanner->routes = $routes;
        
        return response()->json($this->scanner->getTreeData());
    }

    /**
     * Clear route cache
     */
    public function clearCache(): JsonResponse
    {
        $this->scanner->clearCache();
        
        return response()->json([
            'message' => 'Route cache cleared successfully'
        ]);
    }

    /**
     * Apply filters to routes collection
     */
    protected function applyFilters(Collection $routes, Request $request): Collection
    {
        // Search filter
        if ($search = $request->get('search')) {
            $routes = $routes->filter(function ($route) use ($search) {
                return str_contains(strtolower($route['uri']), strtolower($search)) ||
                       str_contains(strtolower($route['name'] ?? ''), strtolower($search)) ||
                       str_contains(strtolower($route['controller']['class'] ?? ''), strtolower($search));
            });
        }

        // Method filter
        if ($method = $request->get('method')) {
            $routes = $routes->filter(function ($route) use ($method) {
                return in_array($method, $route['methods']);
            });
        }

        // Middleware filter
        if ($middleware = $request->get('middleware')) {
            $routes = $routes->filter(function ($route) use ($middleware) {
                return in_array($middleware, $route['middleware']);
            });
        }

        // Domain filter
        if ($domain = $request->get('domain')) {
            $routes = $routes->filter(function ($route) use ($domain) {
                return $route['domain'] === $domain;
            });
        }

        // Namespace filter
        if ($namespace = $request->get('namespace')) {
            $routes = $routes->filter(function ($route) use ($namespace) {
                return $route['namespace'] === $namespace;
            });
        }

        // Middleware group filter
        if ($middlewareGroup = $request->get('middleware_group')) {
            $routes = $routes->filter(function ($route) use ($middlewareGroup) {
                return in_array($middlewareGroup, $route['middleware']);
            });
        }

        return $routes;
    }
}