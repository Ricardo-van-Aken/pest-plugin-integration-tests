<?php

namespace RicardoVanAken\PestPluginE2ETests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class TestingDatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // If the application uses a testing database(when running tests), create the testing connection
        if (str_ends_with(config('database.default'), '_testing')) {
            $this->createTestingConnection(config('database.default'));
        }

        // Switch to testing database if header is present(receiving test requests)
        if ($this->isTestRequest()) {
            // Get the current default connection and switch to its _testing version
            $testingConnection = config('database.default') . '_testing';
            
            // Create the testing connection
            $this->createTestingConnection($testingConnection);
            
            // Only switch if the testing connection exists
            if (config("database.connections.{$testingConnection}")) {
                DB::setDefaultConnection($testingConnection);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Check if the current request is a test request.
     */
    protected function isTestRequest(): bool
    {
        return request()->hasHeader(config('e2e-testing.header_name', 'X-TESTING'));
    }

    /**
     * Create a testing database connection for the given connection name.
     * 
     * @return bool True if the connection was created, false otherwise
     */
    protected function createTestingConnection(string $testingConnection): bool
    {
        // Skip if testing connection already exists, we don't want to overwrite any existing connections
        if (config("database.connections.{$testingConnection}")) {
            Log::warning(
                "[LaravelIntegrationTesting] Testing database connection '{$testingConnection}' already exists. " .
                "Skipping automatic creation. If you want to use the auto-created connection, remove the existing one from your config."
            );
            return false;
        }

        // Check if the base db connection exists, and get the config
        $baseConnection = str_replace('_testing', '', $testingConnection);
        $baseConfig = config("database.connections.{$baseConnection}");
        if (!$baseConfig) {
            Log::warning(
                "[LaravelIntegrationTesting] Base database connection '{$baseConnection}' not found. " .
                "Cannot create testing connection '{$testingConnection}'. " .
                "Make sure the base connection is configured."
            );
            return false;
        }

        // Build testing config based on database type
        $testingConfig = array_merge($baseConfig, [
            'database' => env('DB_DATABASE_TESTING', ''),
        ]);
        if (in_array($baseConnection, ['mysql', 'mariadb', 'pgsql', 'sqlsrv'])) {
            $testingConfig['username'] = env('DB_USERNAME_TESTING', '');
            $testingConfig['password'] = env('DB_PASSWORD_TESTING', '');
        }
        config([
            "database.connections.{$testingConnection}" => $testingConfig,
        ]);

        return true;
    }
}

