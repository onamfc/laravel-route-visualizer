<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Routes Export</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #3b82f6;
        }
        
        .route-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
        }
        
        .method-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .method-get { background: #dcfce7; color: #166534; }
        .method-post { background: #dbeafe; color: #1e40af; }
        .method-put { background: #fef3c7; color: #92400e; }
        .method-patch { background: #fed7aa; color: #9a3412; }
        .method-delete { background: #fecaca; color: #991b1b; }
        
        .route-uri {
            font-family: 'Courier New', monospace;
            font-weight: 500;
        }
        
        .controller-info {
            font-size: 0.875em;
            color: #6b7280;
        }
        
        .middleware-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .middleware-tag {
            background: #f3e8ff;
            color: #7c3aed;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.75em;
        }
        
        .validation-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.75em;
            font-weight: 600;
        }
        
        .validation-error { background: #fecaca; color: #991b1b; }
        .validation-warning { background: #fef3c7; color: #92400e; }
        
        .filter-info {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laravel Routes Export</h1>
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        @if(!empty($filters))
        <div class="filter-info">
            <strong>Applied Filters:</strong>
            @foreach($filters as $key => $value)
                <span class="validation-badge" style="background: #dbeafe; color: #1e40af;">{{ ucfirst($key) }}: {{ $value }}</span>
            @endforeach
        </div>
        @endif
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number">{{ $routes->count() }}</div>
            <div>Total Routes</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $controllers->count() }}</div>
            <div>Controllers</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $middleware->count() + ($middlewareGroups->count() ?? 0) }}</div>
            <div>Middleware</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $methods->count() }}</div>
            <div>HTTP Methods</div>
        </div>
        @if(isset($domains) && $domains->count() > 0)
        <div class="stat-card">
            <div class="stat-number">{{ $domains->count() }}</div>
            <div>Domains</div>
        </div>
        @endif
        @if(isset($namespaces) && $namespaces->count() > 0)
        <div class="stat-card">
            <div class="stat-number">{{ $namespaces->count() }}</div>
            <div>Namespaces</div>
        </div>
        @endif
    </div>

    <div class="route-table">
        <table>
            <thead>
                <tr>
                    <th>Methods</th>
                    <th>URI</th>
                    <th>Name</th>
                    <th>Controller</th>
                    <th>Middleware</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routes as $route)
                <tr>
                    <td>
                        @foreach($route['methods'] as $method)
                            <span class="method-badge method-{{ strtolower($method) }}">{{ $method }}</span>
                        @endforeach
                    </td>
                    <td class="route-uri">{{ $route['uri'] }}</td>
                    <td>{{ $route['name'] ?? '-' }}</td>
                    <td class="controller-info">
                        @if($route['controller'])
                            {{ class_basename($route['controller']['class']) }}@{{ $route['controller']['method'] }}
                            <br>
                            <small>{{ $route['controller']['class'] }}</small>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        <div class="middleware-tags">
                            @foreach($route['middleware'] as $middleware)
                                <span class="middleware-tag">{{ $middleware }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if(isset($route['validation']))
                            @if($route['validation']['missing_controller'] || $route['validation']['missing_method'])
                                <span class="validation-badge validation-error">Error</span>
                            @elseif($route['validation']['is_duplicate'])
                                <span class="validation-badge validation-warning">Duplicate</span>
                            @else
                                <span class="validation-badge" style="background: #dcfce7; color: #166534;">OK</span>
                            @endif
                        @else
                            <span class="validation-badge" style="background: #f3f4f6; color: #6b7280;">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>