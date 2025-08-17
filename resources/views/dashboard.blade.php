<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Route Visualizer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Vis.js for network visualization -->
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

    <!-- D3.js for advanced visualizations -->
    <script src="https://d3js.org/d3.v7.min.js"></script>

    <!-- Mermaid for diagram generation -->
    <script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        #route-network, #d3-network, #tree-view {
            height: 600px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background: #f9fafb;
        }
        
        .dark #route-network, .dark #d3-network, .dark #tree-view {
            background: #1f2937;
            border-color: #374151;
        }
        
        .route-card {
            transition: all 0.2s ease-in-out;
        }
        
        .route-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1);
        }
        
        .dark .route-card:hover {
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.3);
        }
        
        .method-badge {
            @apply px-2 py-1 text-xs font-semibold rounded-full;
        }
        
        .method-get { @apply bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200; }
        .method-post { @apply bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200; }
        .method-put { @apply bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200; }
        .method-patch { @apply bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200; }
        .method-delete { @apply bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200; }
        .method-options { @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200; }
        .method-head { @apply bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200; }
        
        .validation-warning {
            @apply bg-yellow-50 border-l-4 border-yellow-400 p-2 dark:bg-yellow-900 dark:border-yellow-600;
        }
        
        .validation-error {
            @apply bg-red-50 border-l-4 border-red-400 p-2 dark:bg-red-900 dark:border-red-600;
        }
        
        .copy-btn {
            @apply opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-300;
        }
        
        .tree-node {
            cursor: pointer;
            user-select: none;
        }
        
        .tree-node circle {
            fill: #fff;
            stroke: steelblue;
            stroke-width: 3px;
        }
        
        .tree-node text {
            font: 12px sans-serif;
        }
        
        .tree-link {
            fill: none;
            stroke: #ccc;
            stroke-width: 2px;
        }
        
        /* Dark mode styles */
        .dark {
            color-scheme: dark;
        }
        
        .dark .tree-node circle {
            fill: #374151;
            stroke: #60a5fa;
        }
        
        .dark .tree-node text {
            fill: #e5e7eb;
        }
        
        .dark .tree-link {
            stroke: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-200 h-full">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <i class="fas fa-route text-blue-600 dark:text-blue-400 text-2xl mr-3"></i>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Laravel Route Visualizer</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button id="theme-toggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-sun dark:hidden"></i>
                            <i class="fas fa-moon hidden dark:inline"></i>
                        </button>
                        <button id="refresh-btn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-sync-alt mr-2"></i>Refresh
                        </button>
                        <button id="clear-cache-btn" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            <i class="fas fa-trash mr-2"></i>Clear Cache
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8 transition-colors duration-200">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filters & Search</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Routes</label>
                        <input type="text" id="search-input" placeholder="Search by URI, name, or controller..." 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">HTTP Method</label>
                        <select id="method-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Methods</option>
                            <option value="GET">GET</option>
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                            <option value="PATCH">PATCH</option>
                            <option value="DELETE">DELETE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Middleware</label>
                        <select id="middleware-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Middleware</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Domain</label>
                        <select id="domain-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Domains</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Namespace</label>
                        <select id="namespace-filter" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="">All Namespaces</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">View Mode</label>
                        <select id="view-mode" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="list">List View</option>
                            <option value="graph">Graph View</option>
                            <option value="tree">Tree View</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Visualization Library</label>
                        <select id="viz-library" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="vis">Vis.js</option>
                            <option value="d3">D3.js</option>
                            <option value="mermaid">Mermaid</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button id="apply-filters" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Apply Filters
                        </button>
                    </div>
                    <div class="flex items-end">
                        <button id="reset-filters" class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                            <i class="fas fa-route text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Total Routes</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-routes">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                            <i class="fas fa-code text-green-600 dark:text-green-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Controllers</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-controllers">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                            <i class="fas fa-shield-alt text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Middleware</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-middleware">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                            <i class="fas fa-folder text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Prefixes</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-prefixes">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900 rounded-full">
                            <i class="fas fa-globe text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Domains</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-domains">-</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Issues</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-duplicates">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Views -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-200">
                <!-- List View -->
                <div id="list-view" class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Routes List</h2>
                        <div class="text-sm text-gray-500 dark:text-gray-400" id="route-count">
                            Showing <span id="current-count">0</span> of <span id="total-count">0</span> routes
                        </div>
                    </div>
                    <div id="routes-container" class="space-y-4">
                        <!-- Routes will be loaded here -->
                    </div>
                    <div id="load-more-container" class="mt-6 text-center hidden">
                        <button id="load-more-btn" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Load More Routes
                        </button>
                    </div>
                </div>

                <!-- Graph View -->
                <div id="graph-view" class="p-6 hidden">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Routes Network Graph</h2>
                    <div id="route-network"></div>
                    <div id="d3-network" class="hidden"></div>
                    <div id="mermaid-network" class="hidden"></div>
                </div>

                <!-- Tree View -->
                <div id="tree-view-container" class="p-6 hidden">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Routes Hierarchy Tree</h2>
                    <div id="tree-view"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        class RouteVisualizer {
            constructor() {
                this.routes = [];
                this.allRoutes = [];
                this.network = null;
                this.currentPage = 1;
                this.perPage = 50;
                this.hasMore = true;
                this.filters = {};
                this.init();
            }

            init() {
                this.setupEventListeners();
                this.initializeTheme();
                this.loadRoutes();
                mermaid.initialize({ theme: 'default' });
            }

            setupEventListeners() {
                document.getElementById('refresh-btn').addEventListener('click', () => this.loadRoutes());
                document.getElementById('clear-cache-btn').addEventListener('click', () => this.clearCache());
                document.getElementById('apply-filters').addEventListener('click', () => this.applyFilters());
                document.getElementById('reset-filters').addEventListener('click', () => this.resetFilters());
                document.getElementById('view-mode').addEventListener('change', (e) => this.switchView(e.target.value));
                document.getElementById('theme-toggle').addEventListener('click', () => this.toggleTheme());
                document.getElementById('load-more-btn').addEventListener('click', () => this.loadMoreRoutes());
                
                // Real-time search
                document.getElementById('search-input').addEventListener('input', 
                    this.debounce(() => this.applyFilters(), 300)
                );
            }

            initializeTheme() {
                const savedTheme = localStorage.getItem('route-visualizer-theme') || 'light';
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            }

            toggleTheme() {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('route-visualizer-theme', isDark ? 'dark' : 'light');
                
                // Update mermaid theme
                mermaid.initialize({ theme: isDark ? 'dark' : 'default' });
                
                // Re-render current view if it's a graph
                const currentView = document.getElementById('view-mode').value;
                if (currentView === 'graph') {
                    this.renderGraph();
                }
            }

            async loadRoutes(reset = true) {
                if (reset) {
                    this.currentPage = 1;
                    this.routes = [];
                    this.allRoutes = [];
                }

                try {
                    const params = new URLSearchParams({
                        page: this.currentPage,
                        per_page: this.perPage,
                        ...this.filters
                    });

                    const response = await fetch(`/{{ config("route-visualizer.route_prefix") }}/routes?${params}`);
                    const data = await response.json();
                    
                    if (reset) {
                        this.routes = data.routes;
                        this.allRoutes = data.routes;
                    } else {
                        this.routes = [...this.routes, ...data.routes];
                        this.allRoutes = [...this.allRoutes, ...data.routes];
                    }
                    
                    this.hasMore = data.has_more;
                    this.updateStatistics(data);
                    this.populateFilterOptions(data);
                    this.renderRoutes();
                    this.updateLoadMoreButton();
                } catch (error) {
                    console.error('Error loading routes:', error);
                    this.showNotification('Error loading routes', 'error');
                }
            }

            async loadMoreRoutes() {
                this.currentPage++;
                await this.loadRoutes(false);
            }

            updateLoadMoreButton() {
                const container = document.getElementById('load-more-container');
                if (this.hasMore && document.getElementById('view-mode').value === 'list') {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }

            populateFilterOptions(data) {
                // Populate middleware filter
                const middlewareFilter = document.getElementById('middleware-filter');
                const currentMiddleware = middlewareFilter.value;
                middlewareFilter.innerHTML = '<option value="">All Middleware</option>';
                
                Object.keys(data.grouped.middleware).forEach(middleware => {
                    const option = document.createElement('option');
                    option.value = middleware;
                    option.textContent = middleware;
                    if (middleware === currentMiddleware) option.selected = true;
                    middlewareFilter.appendChild(option);
                });

                // Populate domain filter
                const domainFilter = document.getElementById('domain-filter');
                const currentDomain = domainFilter.value;
                domainFilter.innerHTML = '<option value="">All Domains</option>';
                
                Object.keys(data.grouped.domains || {}).forEach(domain => {
                    if (domain && domain !== 'null') {
                        const option = document.createElement('option');
                        option.value = domain;
                        option.textContent = domain;
                        if (domain === currentDomain) option.selected = true;
                        domainFilter.appendChild(option);
                    }
                });

                // Populate namespace filter
                const namespaceFilter = document.getElementById('namespace-filter');
                const currentNamespace = namespaceFilter.value;
                namespaceFilter.innerHTML = '<option value="">All Namespaces</option>';
                
                Object.keys(data.grouped.namespaces || {}).forEach(namespace => {
                    if (namespace && namespace !== 'null') {
                        const option = document.createElement('option');
                        option.value = namespace;
                        option.textContent = namespace;
                        if (namespace === currentNamespace) option.selected = true;
                        namespaceFilter.appendChild(option);
                    }
                });
            }

            async applyFilters() {
                this.filters = {
                    search: document.getElementById('search-input').value,
                    method: document.getElementById('method-filter').value,
                    middleware: document.getElementById('middleware-filter').value,
                    domain: document.getElementById('domain-filter').value,
                    namespace: document.getElementById('namespace-filter').value,
                };

                // Remove empty filters
                Object.keys(this.filters).forEach(key => {
                    if (!this.filters[key]) delete this.filters[key];
                });

                await this.loadRoutes(true);
            }

            resetFilters() {
                document.getElementById('search-input').value = '';
                document.getElementById('method-filter').value = '';
                document.getElementById('middleware-filter').value = '';
                document.getElementById('domain-filter').value = '';
                document.getElementById('namespace-filter').value = '';
                this.filters = {};
                this.loadRoutes(true);
            }

            async clearCache() {
                try {
                    await fetch(`/{{ config("route-visualizer.route_prefix") }}/clear-cache`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                        },
                    });
                    
                    this.showNotification('Cache cleared successfully', 'success');
                    this.loadRoutes();
                } catch (error) {
                    console.error('Error clearing cache:', error);
                    this.showNotification('Error clearing cache', 'error');
                }
            }

            updateStatistics(data) {
                document.getElementById('total-routes').textContent = data.total;
                document.getElementById('total-controllers').textContent = Object.keys(data.grouped.controllers).length;
                document.getElementById('total-middleware').textContent = Object.keys(data.grouped.middleware).length;
                document.getElementById('total-prefixes').textContent = Object.keys(data.grouped.prefixes).length;
                document.getElementById('total-domains').textContent = Object.keys(data.grouped.domains || {}).length;
                document.getElementById('total-duplicates').textContent = data.duplicates || 0;
                
                // Update route count
                document.getElementById('current-count').textContent = this.routes.length;
                document.getElementById('total-count').textContent = data.total;
            }

            renderRoutes() {
                const container = document.getElementById('routes-container');
                if (this.currentPage === 1) {
                    container.innerHTML = '';
                }

                const newRoutes = this.currentPage === 1 ? this.routes : this.routes.slice((this.currentPage - 1) * this.perPage);
                
                newRoutes.forEach(route => {
                    const routeCard = this.createRouteCard(route);
                    container.appendChild(routeCard);
                });
            }

            createRouteCard(route) {
                const card = document.createElement('div');
                card.className = 'route-card bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 group transition-colors duration-200';
                
                const methodBadges = route.methods.map(method => 
                    `<span class="method-badge method-${method.toLowerCase()}">${method}</span>`
                ).join(' ');

                const middlewareBadges = route.middleware.map(middleware =>
                    `<span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 text-xs rounded">${middleware}</span>`
                ).join(' ');

                let validationHtml = '';
                if (route.validation) {
                    if (route.validation.missing_controller || route.validation.missing_method) {
                        validationHtml = `
                            <div class="validation-error mt-2">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                                    <span class="text-sm font-medium text-red-800 dark:text-red-200">Validation Error</span>
                                </div>
                                ${route.validation.warnings.map(warning => 
                                    `<p class="text-xs text-red-700 dark:text-red-300 mt-1">${warning}</p>`
                                ).join('')}
                            </div>
                        `;
                    } else if (route.validation.is_duplicate) {
                        validationHtml = `
                            <div class="validation-warning mt-2">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>
                                    <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Duplicate Route</span>
                                </div>
                            </div>
                        `;
                    }
                }

                card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center space-x-2">
                            ${methodBadges}
                        </div>
                        ${route.name ? `<span class="text-sm text-gray-500 dark:text-gray-400">${route.name}</span>` : ''}
                    </div>
                    
                    <div class="mb-3">
                        <div class="flex items-center group">
                            <h3 class="text-lg font-mono font-semibold text-gray-900 dark:text-white mr-2">${route.uri}</h3>
                            <button class="copy-btn" onclick="navigator.clipboard.writeText('${route.uri}')">
                                <i class="fas fa-copy text-sm"></i>
                            </button>
                        </div>
                        ${route.controller ? `
                            <div class="flex items-center group mt-1">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mr-2">
                                    <i class="fas fa-code mr-1"></i>
                                    ${route.controller.class}@${route.controller.method}
                                </p>
                                <button class="copy-btn" onclick="navigator.clipboard.writeText('${route.controller.class}@${route.controller.method}')">
                                    <i class="fas fa-copy text-sm"></i>
                                </button>
                            </div>
                        ` : ''}
                        ${route.domain ? `
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <i class="fas fa-globe mr-1"></i>
                                Domain: ${route.domain}
                            </p>
                        ` : ''}
                        ${route.namespace ? `
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                <i class="fas fa-folder mr-1"></i>
                                Namespace: ${route.namespace}
                            </p>
                        ` : ''}
                    </div>
                    
                    ${route.middleware.length > 0 ? `
                        <div class="mb-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-2">Middleware:</span>
                            ${middlewareBadges}
                        </div>
                    ` : ''}
                    
                    ${route.parameters.length > 0 ? `
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-cog mr-1"></i>
                            Parameters: ${route.parameters.join(', ')}
                        </div>
                    ` : ''}
                    
                    ${validationHtml}
                    
                    @if(config('route-visualizer.integrations.telescope.enabled'))
                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                        <a href="{{ config('route-visualizer.integrations.telescope.base_url') }}/requests?filter[uri]=${encodeURIComponent(route.uri)}" 
                           target="_blank" 
                           class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            <i class="fas fa-external-link-alt mr-1"></i>
                            View in Telescope
                        </a>
                    </div>
                    @endif
                `;

                return card;
            }

            async switchView(mode) {
                const listView = document.getElementById('list-view');
                const graphView = document.getElementById('graph-view');
                const treeView = document.getElementById('tree-view-container');

                // Hide all views
                [listView, graphView, treeView].forEach(view => view.classList.add('hidden'));

                switch (mode) {
                    case 'graph':
                        graphView.classList.remove('hidden');
                        await this.renderGraph();
                        break;
                    case 'tree':
                        treeView.classList.remove('hidden');
                        await this.renderTree();
                        break;
                    default:
                        listView.classList.remove('hidden');
                        this.updateLoadMoreButton();
                        break;
                }
            }

            async renderGraph() {
                const library = document.getElementById('viz-library').value;
                
                // Hide all graph containers
                document.getElementById('route-network').classList.add('hidden');
                document.getElementById('d3-network').classList.add('hidden');
                document.getElementById('mermaid-network').classList.add('hidden');

                try {
                    const params = new URLSearchParams({ type: library, ...this.filters });
                    const response = await fetch(`/{{ config("route-visualizer.route_prefix") }}/graph?${params}`);
                    const data = await response.json();
                    
                    switch (library) {
                        case 'd3':
                            await this.renderD3Graph(data);
                            break;
                        case 'mermaid':
                            await this.renderMermaidGraph(data);
                            break;
                        default:
                            await this.renderVisGraph(data);
                            break;
                    }
                } catch (error) {
                    console.error('Error rendering graph:', error);
                }
            }

            async renderVisGraph(data) {
                const container = document.getElementById('route-network');
                container.classList.remove('hidden');
                
                const options = {
                    nodes: {
                        shape: 'dot',
                        size: 16,
                        font: {
                            size: 12,
                            color: document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#333333'
                        },
                        borderWidth: 2,
                        shadow: true
                    },
                    edges: {
                        width: 2,
                        color: { inherit: 'from' },
                        smooth: {
                            type: 'continuous'
                        },
                        arrows: {
                            to: { enabled: true, scaleFactor: 1, type: 'arrow' }
                        }
                    },
                    groups: {
                        controller: {
                            color: { background: '#3B82F6', border: '#1E40AF' }
                        },
                        route: {
                            color: { background: '#10B981', border: '#059669' }
                        }
                    },
                    physics: {
                        stabilization: { iterations: 150 }
                    }
                };

                this.network = new vis.Network(container, data, options);
                
                this.network.on('click', (params) => {
                    if (params.nodes.length > 0) {
                        const nodeId = params.nodes[0];
                        console.log('Clicked node:', nodeId);
                    }
                });
            }

            async renderD3Graph(data) {
                const container = document.getElementById('d3-network');
                container.classList.remove('hidden');
                container.innerHTML = '';

                const width = container.clientWidth;
                const height = 600;

                const svg = d3.select(container)
                    .append('svg')
                    .attr('width', width)
                    .attr('height', height);

                const simulation = d3.forceSimulation(data.nodes)
                    .force('link', d3.forceLink(data.links).id(d => d.id))
                    .force('charge', d3.forceManyBody().strength(-300))
                    .force('center', d3.forceCenter(width / 2, height / 2));

                const link = svg.append('g')
                    .selectAll('line')
                    .data(data.links)
                    .enter().append('line')
                    .attr('stroke-width', 2)
                    .attr('stroke', '#999');

                const node = svg.append('g')
                    .selectAll('circle')
                    .data(data.nodes)
                    .enter().append('circle')
                    .attr('r', 8)
                    .attr('fill', d => d.group === 'controller' ? '#3B82F6' : '#10B981')
                    .call(d3.drag()
                        .on('start', dragstarted)
                        .on('drag', dragged)
                        .on('end', dragended));

                const label = svg.append('g')
                    .selectAll('text')
                    .data(data.nodes)
                    .enter().append('text')
                    .text(d => d.name)
                    .attr('font-size', 12)
                    .attr('dx', 12)
                    .attr('dy', 4)
                    .attr('fill', document.documentElement.classList.contains('dark') ? '#e5e7eb' : '#333');

                simulation.on('tick', () => {
                    link
                        .attr('x1', d => d.source.x)
                        .attr('y1', d => d.source.y)
                        .attr('x2', d => d.target.x)
                        .attr('y2', d => d.target.y);

                    node
                        .attr('cx', d => d.x)
                        .attr('cy', d => d.y);

                    label
                        .attr('x', d => d.x)
                        .attr('y', d => d.y);
                });

                function dragstarted(event, d) {
                    if (!event.active) simulation.alphaTarget(0.3).restart();
                    d.fx = d.x;
                    d.fy = d.y;
                }

                function dragged(event, d) {
                    d.fx = event.x;
                    d.fy = event.y;
                }

                function dragended(event, d) {
                    if (!event.active) simulation.alphaTarget(0);
                    d.fx = null;
                    d.fy = null;
                }
            }

            async renderMermaidGraph(data) {
                const container = document.getElementById('mermaid-network');
                container.classList.remove('hidden');
                container.innerHTML = `<div class="mermaid">${data.mermaid}</div>`;
                
                await mermaid.init();
            }

            async renderTree() {
                try {
                    const response = await fetch(`/{{ config("route-visualizer.route_prefix") }}/tree-data`);
                    const data = await response.json();
                    
                    const container = document.getElementById('tree-view');
                    container.innerHTML = '';

                    const width = container.clientWidth;
                    const height = 600;

                    const svg = d3.select(container)
                        .append('svg')
                        .attr('width', width)
                        .attr('height', height);

                    const g = svg.append('g')
                        .attr('transform', 'translate(40,0)');

                    const tree = d3.tree()
                        .size([height, width - 160]);

                    const root = d3.hierarchy(this.convertToHierarchy(data.tree));
                    tree(root);

                    const link = g.selectAll('.tree-link')
                        .data(root.descendants().slice(1))
                        .enter().append('path')
                        .attr('class', 'tree-link')
                        .attr('d', d => {
                            return `M${d.y},${d.x}C${(d.y + d.parent.y) / 2},${d.x} ${(d.y + d.parent.y) / 2},${d.parent.x} ${d.parent.y},${d.parent.x}`;
                        });

                    const node = g.selectAll('.tree-node')
                        .data(root.descendants())
                        .enter().append('g')
                        .attr('class', 'tree-node')
                        .attr('transform', d => `translate(${d.y},${d.x})`);

                    node.append('circle')
                        .attr('r', 6);

                    node.append('text')
                        .attr('dy', '.35em')
                        .attr('x', d => d.children ? -13 : 13)
                        .style('text-anchor', d => d.children ? 'end' : 'start')
                        .text(d => d.data.name);

                } catch (error) {
                    console.error('Error rendering tree:', error);
                }
            }

            convertToHierarchy(treeData) {
                // Convert flat tree data to d3 hierarchy format
                const root = { name: 'Routes', children: [] };
                
                Object.values(treeData).forEach(node => {
                    if (node.routes && node.routes.length > 0) {
                        const treeNode = {
                            name: node.name || 'root',
                            children: node.routes.map(route => ({
                                name: `${route.methods.join('|')} ${route.uri}`,
                                route: route
                            }))
                        };
                        root.children.push(treeNode);
                    }
                });
                
                return root;
            }

            showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-500 text-white' : 
                    type === 'error' ? 'bg-red-500 text-white' : 
                    'bg-blue-500 text-white'
                }`;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }

            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }

        // Initialize the visualizer when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            new RouteVisualizer();
        });
    </script>
</body>
</html>