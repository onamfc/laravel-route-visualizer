<?php

namespace onamfc\LaravelRouteVisualizer\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Illuminate\Routing\Router;
use onamfc\LaravelRouteVisualizer\Services\RouteScanner;
use onamfc\LaravelRouteVisualizer\RouteVisualizerServiceProvider;

class RouteScannerTest extends TestCase
{
    protected RouteScanner $scanner;

    protected function getPackageProviders($app)
    {
        return [RouteVisualizerServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = $this->app->make(RouteScanner::class);
    }

    /** @test */
    public function it_can_extract_route_data()
    {
        $router = $this->app->make(Router::class);
        $router->get('/test', function () {
            return 'test';
        })->name('test.route');

        $routes = $this->scanner->scanRoutesCollection();
        $testRoute = $routes->firstWhere('name', 'test.route');

        $this->assertNotNull($testRoute);
        $this->assertEquals('/test', $testRoute['uri']);
        $this->assertContains('GET', $testRoute['methods']);
    }

    /** @test */
    public function it_can_parse_controller_actions()
    {
        $router = $this->app->make(Router::class);
        $router->get('/controller-test', 'TestController@index');

        $routes = $this->scanner->scanRoutesCollection();
        $controllerRoute = $routes->firstWhere('uri', '/controller-test');

        $this->assertNotNull($controllerRoute);
        $this->assertEquals('TestController', $controllerRoute['controller']['class']);
        $this->assertEquals('index', $controllerRoute['controller']['method']);
    }

    /** @test */
    public function it_can_group_routes_by_controller()
    {
        $router = $this->app->make(Router::class);
        $router->get('/test1', 'TestController@index');
        $router->get('/test2', 'TestController@show');

        $grouped = $this->scanner->getRoutesGroupedByController();

        $this->assertArrayHasKey('TestController', $grouped->toArray());
        $this->assertCount(2, $grouped->get('TestController'));
    }

    /** @test */
    public function it_can_detect_duplicate_routes()
    {
        $router = $this->app->make(Router::class);
        $router->get('/duplicate', function () { return 'first'; });
        $router->get('/duplicate', function () { return 'second'; });

        $duplicates = $this->scanner->findDuplicateRoutes();

        $this->assertGreaterThan(0, $duplicates->count());
    }

    /** @test */
    public function it_can_validate_missing_controllers()
    {
        config(['route-visualizer.validation.check_missing_controllers' => true]);

        $router = $this->app->make(Router::class);
        $router->get('/missing', 'NonExistentController@index');

        $routes = $this->scanner->scanRoutesCollection();
        $missingRoute = $routes->firstWhere('uri', '/missing');

        $this->assertNotNull($missingRoute);
        $this->assertTrue($missingRoute['validation']['missing_controller']);
    }

    /** @test */
    public function it_can_get_tree_data()
    {
        $router = $this->app->make(Router::class);
        $router->get('/api/users', function () { return 'users'; });
        $router->get('/api/posts', function () { return 'posts'; });

        $treeData = $this->scanner->getTreeData();

        $this->assertIsArray($treeData);
        $this->assertArrayHasKey('api', $treeData);
    }

    /** @test */
    public function it_filters_sensitive_routes()
    {
        config(['route-visualizer.security.hide_sensitive_routes' => true]);
        config(['route-visualizer.security.sensitive_patterns' => ['admin/*']]);

        $router = $this->app->make(Router::class);
        $router->get('/admin/users', function () {
            return 'admin';
        });
        $router->get('/public/users', function () {
            return 'public';
        });

        $routes = $this->scanner->scanRoutesCollection();

        $this->assertNull($routes->firstWhere('uri', '/admin/users'));
        $this->assertNotNull($routes->firstWhere('uri', '/public/users'));
    }
}