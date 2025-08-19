<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Visualizer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Vis.js for network visualization -->
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        #route-network, #tree-view {
            height: 800px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background: #f9fafb;
            overflow-y: auto;
        }
        
        .dark #route-network, .dark #tree-view {
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
            fill: #374151;
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Route Visualizer</h1>
                    </div>
                    <div class="flex items-center space-x-4">
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            }

            setupEventListeners() {
                document.getElementById('refresh-btn').addEventListener('click', () => this.loadRoutes());
                document.getElementById('clear-cache-btn').addEventListener('click', () => this.clearCache());
                document.getElementById('apply-filters').addEventListener('click', () => this.applyFilters());
                document.getElementById('reset-filters').addEventListener('click', () => this.resetFilters());
                document.getElementById('view-mode').addEventListener('change', (e) => this.switchView(e.target.value));
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
                
                // Update current view if it's graph or tree
                const currentView = document.getElementById('view-mode').value;
                if (currentView === 'graph') {
                    await this.renderGraph();
                } else if (currentView === 'tree') {
                    await this.renderTree();
                }
            }

            resetFilters() {
                document.getElementById('search-input').value = '';
                document.getElementById('method-filter').value = '';
                document.getElementById('middleware-filter').value = '';
                document.getElementById('domain-filter').value = '';
                document.getElementById('namespace-filter').value = '';
                this.filters = {};
                this.loadRoutes(true).then(() => {
                    // Update current view if it's graph or tree
                    const currentView = document.getElementById('view-mode').value;
                    if (currentView === 'graph') {
                        this.renderGraph();
                    } else if (currentView === 'tree') {
                        this.renderTree();
                    }
                });
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
                const container = document.getElementById('route-network');
                container.classList.remove('hidden');

                try {
                    const params = new URLSearchParams({ type: 'vis', ...this.filters });
                    const response = await fetch(`/{{ config("route-visualizer.route_prefix") }}/graph?${params}`);
                    const data = await response.json();

                    await this.renderVisGraph(data);
                } catch (error) {
                    console.error('Error rendering graph:', error);
                }
            }

            async renderVisGraph(data) {
                const container = document.getElementById('route-network');
                
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

            async renderTree() {
                try {
                    const params = new URLSearchParams(this.filters);
                    const response = await fetch(`/{{ config("route-visualizer.route_prefix") }}/tree-data?${params}`);
                    const data = await response.json();
                    
                    const container = document.getElementById('tree-view');
                    container.innerHTML = '';

                    const width = container.clientWidth;
                    const height = 600;

                    // Simple tree visualization using HTML/CSS instead of D3
                    this.renderSimpleTree(container, data.tree);

                } catch (error) {
                    console.error('Error rendering tree:', error);
                }
            }

            renderSimpleTree(container, treeData) {
                container.innerHTML = '';
                const treeDiv = document.createElement('div');
                treeDiv.className = 'p-4 space-y-4';

                this.renderTreeNode(treeData, treeDiv, 0);

                container.appendChild(treeDiv);
            }

            renderTreeNode(nodeData, parentElement, depth) {
                Object.entries(nodeData).forEach(([key, node]) => {
                    const nodeDiv = document.createElement('div');
                    nodeDiv.className = `ml-${depth * 4} mb-2`;
                    
                    // Create collapsible node header
                    const nodeHeader = document.createElement('div');
                    nodeHeader.className = 'flex items-center cursor-pointer p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors';
                    
                    // Expand/collapse icon
                    const hasChildren = Object.keys(node.children || {}).length > 0;
                    const hasRoutes = (node.routes || []).length > 0;
                    
                    if (hasChildren || hasRoutes) {
                        const toggleIcon = document.createElement('i');
                        toggleIcon.className = 'fas fa-chevron-right text-gray-400 mr-2 transition-transform';
                        toggleIcon.style.fontSize = '12px';
                        nodeHeader.appendChild(toggleIcon);
                    } else {
                        const spacer = document.createElement('span');
                        spacer.className = 'w-4 mr-2';
                        nodeHeader.appendChild(spacer);
                    }
                    
                    // Folder/route icon
                    const icon = document.createElement('i');
                    if (hasChildren) {
                        icon.className = 'fas fa-folder text-blue-500 mr-2';
                    } else if (hasRoutes) {
                        icon.className = 'fas fa-file text-green-500 mr-2';
                    } else {
                        icon.className = 'fas fa-circle text-gray-400 mr-2';
                        icon.style.fontSize = '8px';
                    }
                    nodeHeader.appendChild(icon);
                    
                    // Node name
                    const nodeName = document.createElement('span');
                    nodeName.className = 'font-medium text-gray-900 dark:text-white';
                    nodeName.textContent = key === '' ? '/' : key;
                    nodeHeader.appendChild(nodeName);
                    
                    // Route count badge
                    if (hasRoutes) {
                        const badge = document.createElement('span');
                        badge.className = 'ml-2 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-full';
                        badge.textContent = `${node.routes.length} route${node.routes.length !== 1 ? 's' : ''}`;
                        nodeHeader.appendChild(badge);
                    }
                    
                    nodeDiv.appendChild(nodeHeader);
                    
                    // Create collapsible content
                    const nodeContent = document.createElement('div');
                    nodeContent.className = 'hidden ml-6 mt-2';
                    
                    // Add routes for this node
                    if (hasRoutes) {
                        const routesContainer = document.createElement('div');
                        routesContainer.className = 'space-y-1 mb-3';
                        
                        node.routes.forEach(route => {
                            const routeDiv = document.createElement('div');
                            routeDiv.className = 'flex items-center space-x-2 text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded border-l-2 border-green-400';
                            
                            const methodSpan = document.createElement('span');
                            methodSpan.className = `px-2 py-1 rounded text-xs font-semibold method-${route.methods[0].toLowerCase()}`;
                            methodSpan.textContent = route.methods.join('|');
                            
                            const uriSpan = document.createElement('span');
                            uriSpan.className = 'font-mono text-gray-700 dark:text-gray-300 flex-1';
                            uriSpan.textContent = route.uri;
                            
                            const nameSpan = document.createElement('span');
                            nameSpan.className = 'text-xs text-gray-500 dark:text-gray-400';
                            nameSpan.textContent = route.name || '';
                            
                            routeDiv.appendChild(methodSpan);
                            routeDiv.appendChild(uriSpan);
                            if (route.name) {
                                routeDiv.appendChild(nameSpan);
                            }
                            
                            routesContainer.appendChild(routeDiv);
                        });
                        
                        nodeContent.appendChild(routesContainer);
                    }
                    
                    // Recursively render children
                    if (hasChildren) {
                        this.renderTreeNode(node.children, nodeContent, depth + 1);
                    }
                    
                    nodeDiv.appendChild(nodeContent);
                    
                    // Add click handler for expand/collapse
                    if (hasChildren || hasRoutes) {
                        nodeHeader.addEventListener('click', () => {
                            const isExpanded = !nodeContent.classList.contains('hidden');
                            const toggleIcon = nodeHeader.querySelector('i');
                            
                            if (isExpanded) {
                                nodeContent.classList.add('hidden');
                                toggleIcon.style.transform = 'rotate(0deg)';
                            } else {
                                nodeContent.classList.remove('hidden');
                                toggleIcon.style.transform = 'rotate(90deg)';
                            }
                        });
                    }
                    
                    parentElement.appendChild(nodeDiv);
                });
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