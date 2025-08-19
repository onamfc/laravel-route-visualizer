<?php

namespace onamfc\LaravelRouteVisualizer\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'route-visualizer:install 
                           {--force : Overwrite existing files}';

    protected $description = 'Install the Laravel Route Visualizer package';

    public function handle(): int
    {
        $this->info('Installing Laravel Route Visualizer...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'route-visualizer-config',
            '--force' => $this->option('force'),
        ]);

        // Publish views
        $this->call('vendor:publish', [
            '--tag' => 'route-visualizer-views',
            '--force' => $this->option('force'),
        ]);

        // Publish assets
        $this->call('vendor:publish', [
            '--tag' => 'route-visualizer-assets',
            '--force' => $this->option('force'),
        ]);

        $this->info('Laravel Route Visualizer installed successfully!');
        $this->info('');
        $this->info('Next steps:');
        $this->info('1. Visit /route-visualizer to access the dashboard');
        $this->info('2. Customize config/route-visualizer.php as needed');
        $this->info('3. Run php artisan route:export to generate static exports');

        return Command::SUCCESS;
    }
}