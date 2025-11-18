<?php

namespace RicardoVanAken\PestPluginE2ETests;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class TestingCacheServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Switch cache to testing storage if header is present (receiving test requests)
        if ($this->isTestRequest()) {
            $this->switchCacheToTestingStorage();
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
     * Switch the cache configuration to use testing storage for all cache drivers.
     */
    protected function switchCacheToTestingStorage(): void
    {
        $cacheDriver = config('cache.default');

        switch ($cacheDriver) {
            case 'redis':
                $this->switchRedisCacheToTesting();
                break;

            case 'database':
                $this->switchDatabaseCacheToTesting();
                break;

            case 'file':
                $this->switchFileCacheToTesting();
                break;

            case 'array':
                // Array cache is already isolated per process, no action needed
                break;

            case 'memcached':
                // Memcached would require a separate server or namespace
                // For now, we'll use a testing prefix to isolate keys
                $this->switchMemcachedCacheToTesting();
                break;

            case 'dynamodb':
                // DynamoDB would use a separate table for testing
                $this->switchDynamoDBCacheToTesting();
                break;

            case 'octane':
                // Octane cache is already isolated per process
                break;

            default:
                // Unknown cache driver, log a warning
                Log::warning(
                    "[LaravelIntegrationTesting] Unknown cache driver '{$cacheDriver}'. " .
                    "Cache isolation for testing may not work correctly."
                );
                break;
        }
    }

    /**
     * Switch Redis cache to use the testing database.
     */
    protected function switchRedisCacheToTesting(): void
    {
        // Get the current cache connection
        $cacheConnection = config('cache.stores.redis.connection', 'cache');
        $redisConfig = config("database.redis.{$cacheConnection}", []);

        if (empty($redisConfig)) {
            Log::warning(
                "[LaravelIntegrationTesting] Redis config not found for connection '{$cacheConnection}'. " .
                "Cache isolation for testing may not work correctly."
            );
            return;
        }

        $currentCacheDb = $redisConfig['database'] ?? 1;
        $testingCacheDb = (int) env('REDIS_CACHE_DB_TESTING', 15);

        // Check if the testing and application redis cache databases are the same.
        if ($testingCacheDb === $currentCacheDb) {
            Log::warning(
                "[LaravelIntegrationTesting] Redis cache database for testing is the same as the applications Redis " .
                "cache database. This will cause tests to use the same cache as the application. " .
                "\nCurrent database number: {$currentCacheDb}. " .
                "\nTesting database number: {$testingCacheDb}. " .
                "\nPlease set REDIS_CACHE_DB_TESTING to a different database number."
            );
            return;
        }

        // Switch the used redis cache database to the testing cache database.
        config([
            "database.redis.{$cacheConnection}.database" => $testingCacheDb,
        ]);
    }

    /**
     * Switch database cache to use the testing database connection.
     */
    protected function switchDatabaseCacheToTesting(): void
    {
        // Get the current default connection and switch to its _testing version
        $testingConnection = config('database.default') . '_testing';

        // Ensure the testing connection exists
        if (config("database.connections.{$testingConnection}")) {
            // Update the cache store to use the testing database connection
            config([
                'cache.stores.database.connection' => $testingConnection,
            ]);
        }
    }

    /**
     * Switch file cache to use a testing directory.
     */
    protected function switchFileCacheToTesting(): void
    {
        $testingPath = storage_path('framework/cache/testing');

        // Update the cache store to use the testing directory
        config([
            'cache.stores.file.path' => $testingPath,
            'cache.stores.file.lock_path' => $testingPath,
        ]);
    }

    /**
     * Switch Memcached cache to use a testing prefix.
     */
    protected function switchMemcachedCacheToTesting(): void
    {
        // Memcached doesn't support separate databases, so we use a prefix
        // This requires the cache prefix to be set differently for tests
        $testingPrefix = config('cache.prefix', 'laravel') . '-testing-';
        config([
            'cache.prefix' => $testingPrefix,
        ]);
    }

    /**
     * Switch DynamoDB cache to use a testing table.
     */
    protected function switchDynamoDBCacheToTesting(): void
    {
        $testingTable = env('DYNAMODB_CACHE_TABLE', 'cache') . '_testing';
        config([
            'cache.stores.dynamodb.table' => $testingTable,
        ]);
    }
}

