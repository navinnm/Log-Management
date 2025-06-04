<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class LogManagementInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'log-management:install 
                            {--force : Overwrite existing files}
                            {--skip-migrations : Skip running migrations}
                            {--skip-config : Skip publishing configuration}';

    /**
     * The console command description.
     */
    protected $description = 'Install the Log Management package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing Log Management Package...');

        // Publish configuration
        if (!$this->option('skip-config')) {
            $this->publishConfiguration();
        }

        // Run migrations
        if (!$this->option('skip-migrations')) {
            $this->runMigrations();
        }

        // Create directories
        $this->createDirectories();

        // Generate API keys
        $this->generateApiKeys();

        // Display completion message
        $this->displayCompletionMessage();

        return Command::SUCCESS;
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->info('Publishing configuration...');

        $configExists = File::exists(config_path('log-management.php'));

        if ($configExists && !$this->option('force')) {
            if (!$this->confirm('Configuration file already exists. Do you want to overwrite it?')) {
                $this->warn('Skipping configuration publish.');
                return;
            }
        }

        Artisan::call('vendor:publish', [
            '--provider' => 'Fulgid\LogManagement\LogManagementServiceProvider',
            '--tag' => 'log-management-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('Configuration published successfully.');
    }

    /**
     * Run the package migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('Running migrations...');

        try {
            Artisan::call('migrate', [
                '--path' => 'vendor/fulgid/log-management/database/migrations',
                '--force' => true,
            ]);

            $this->info('Migrations completed successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to run migrations: ' . $e->getMessage());
            
            if ($this->confirm('Do you want to continue with the installation?')) {
                return;
            }
            
            exit(1);
        }
    }

    /**
     * Create necessary directories.
     */
    protected function createDirectories(): void
    {
        $this->info('Creating directories...');

        $directories = [
            storage_path('logs/log-management'),
            storage_path('app/log-management'),
        ];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("Created directory: {$directory}");
            }
        }
    }

    /**
     * Generate API keys for authentication.
     */
    protected function generateApiKeys(): void
    {
        if (!$this->confirm('Do you want to generate API keys for authentication?', true)) {
            return;
        }

        $this->info('Generating API keys...');

        $envFile = base_path('.env');
        $envContent = File::exists($envFile) ? File::get($envFile) : '';

        $apiKeys = [
            'LOG_MANAGEMENT_API_KEY_1' => $this->generateApiKey(),
            'LOG_MANAGEMENT_API_KEY_2' => $this->generateApiKey(),
        ];

        foreach ($apiKeys as $key => $value) {
            if (strpos($envContent, $key) === false) {
                $envContent .= "\n{$key}={$value}";
                $this->line("Generated {$key}");
            } else {
                $this->warn("{$key} already exists in .env file");
            }
        }

        File::put($envFile, $envContent);
        $this->info('API keys added to .env file');
    }

    /**
     * Generate a secure API key.
     */
    protected function generateApiKey(): string
    {
        return 'lm_' . bin2hex(random_bytes(32));
    }

    /**
     * Display completion message with next steps.
     */
    protected function displayCompletionMessage(): void
    {
        $this->newLine();
        $this->info('🎉 Log Management Package installed successfully!');
        $this->newLine();

        $this->comment('Next steps:');
        $this->line('1. Configure your notification channels in config/log-management.php');
        $this->line('2. Set up your environment variables in .env file');
        $this->line('3. Test your configuration with: php artisan log-management:test');
        $this->newLine();

        $this->comment('Available endpoints:');
        $this->line('• Real-time logs: GET /log-management/stream');
        $this->line('• API logs: GET /log-management/api/logs');
        $this->line('• Health check: GET /log-management/health');
        $this->line('• Dashboard: GET /log-management/dashboard');
        $this->newLine();

        $this->comment('Environment variables to configure:');
        $envVars = [
            'LOG_MANAGEMENT_ENABLED' => 'true',
            'LOG_MANAGEMENT_NOTIFICATIONS_ENABLED' => 'true',
            'LOG_MANAGEMENT_EMAIL_ENABLED' => 'false',
            'LOG_MANAGEMENT_EMAIL_TO' => 'your-email@example.com',
            'LOG_MANAGEMENT_SLACK_ENABLED' => 'false',
            'LOG_MANAGEMENT_SLACK_WEBHOOK' => 'your-slack-webhook-url',
            'LOG_MANAGEMENT_WEBHOOK_ENABLED' => 'false',
            'LOG_MANAGEMENT_WEBHOOK_URL' => 'your-webhook-url',
        ];

        foreach ($envVars as $key => $example) {
            $this->line("• {$key}={$example}");
        }

        $this->newLine();
        $this->warn('Remember to configure your web server to handle Server-Sent Events properly!');
    }
}