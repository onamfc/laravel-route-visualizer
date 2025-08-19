<?php

namespace onamfc\LaravelRouteVisualizer\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'route-visualizer:install';

    /**
     * The console command description.
     */
    protected $description = 'Install the Laravel Route Visualizer package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Laravel Route Visualizer...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'route-visualizer-config',
            '--force' => true,
        ]);

        $this->info('✓ Configuration file published');

        // Ask if user wants to publish views
        if ($this->confirm('Do you want to publish the views for customization?', false)) {
            $this->call('vendor:publish', [
                '--tag' => 'route-visualizer-views',
                '--force' => true,
            ]);
            $this->info('✓ Views published');
        }

        $this->newLine();
        $this->info('🎉 Laravel Route Visualizer installed successfully!');
        $this->newLine();
        
        $this->line('Next steps:');
        $this->line('1. Visit <comment>/route-visualizer</comment> to access the dashboard');
        $this->line('2. Configure settings in <comment>config/route-visualizer.php</comment>');
        $this->line('3. Run <comment>php artisan route:export</comment> to export routes');
        
        $this->newLine();
        $this->warn('Note: The visualizer is only enabled in local and testing environments by default.');
        $this->line('Set <comment>ROUTE_VISUALIZER_ENABLED=true</comment> in your .env file to enable in other environments.');

        return self::SUCCESS;
    }
}