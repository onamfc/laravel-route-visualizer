<?php

namespace onamfc\LaravelRouteVisualizer\Tests\Feature;

use Orchestra\Testbench\TestCase;
use onamfc\LaravelRouteVisualizer\RouteVisualizerServiceProvider;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;

class RouteVisualizerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [RouteVisualizerServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('route-visualizer.enabled', true);
    }

    /** @test */
    public function it_can_scan_routes()
    {
        $scanner = $this->app->make(RouteScanner::class);
        $routes = $scanner->scanRoutes();
        
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $routes);
    }

    /** @test */
    public function dashboard_is_accessible()
    {
        $response = $this->get('/route-visualizer');
        $response->assertStatus(200);
    }

    /** @test */
    public function api_endpoint_returns_json()
    {
        $response = $this->get('/route-visualizer/routes');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'routes',
            'total',
            'grouped'
        ]);
    }

    /** @test */
    public function can_filter_routes_by_method()
    {
        $response = $this->get('/route-visualizer/routes?method=GET');
        $response->assertStatus(200);
        
        $data = $response->json();
        foreach ($data['routes'] as $route) {
            $this->assertContains('GET', $route['methods']);
        }
    }

    /** @test */
    public function can_filter_routes_by_domain()
    {
        $response = $this->get('/route-visualizer/routes?domain=api.example.com');
        $response->assertStatus(200);
        
        $data = $response->json();
        foreach ($data['routes'] as $route) {
            $this->assertEquals('api.example.com', $route['domain']);
        }
    }

    /** @test */
    public function can_filter_routes_by_namespace()
    {
        $response = $this->get('/route-visualizer/routes?namespace=App\\Http\\Controllers\\Api');
        $response->assertStatus(200);
        
        $data = $response->json();
        foreach ($data['routes'] as $route) {
            $this->assertEquals('App\\Http\\Controllers\\Api', $route['namespace']);
        }
    }

    /** @test */
    public function can_get_tree_data()
    {
        $response = $this->get('/route-visualizer/tree-data');
        $response->assertStatus(200);
        $response->assertJsonStructure(['tree']);
    }

    /** @test */
    public function can_get_d3_graph_data()
    {
        $response = $this->get('/route-visualizer/graph?type=d3');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'nodes',
            'links'
        ]);
    }

    /** @test */
    public function can_get_mermaid_graph_data()
    {
        $response = $this->get('/route-visualizer/graph?type=mermaid');
        $response->assertStatus(200);
        $response->assertJsonStructure(['mermaid']);
    }

    /** @test */
    public function can_clear_cache()
    {
        $response = $this->post('/route-visualizer/clear-cache');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Route cache cleared successfully']);
    }
}