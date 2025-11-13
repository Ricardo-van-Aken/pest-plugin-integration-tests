<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginIntegrationTests;

use Pest\Contracts\Plugins\Bootable;
use RicardoVanAken\PestPluginIntegrationTests\IntegrationTestCase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * @internal
 */
final class Plugin implements Bootable
{
    /**
     * Boots the plugin.
     * This is called after Pest initializes, so we can safely use pest()->extend()
     */
    public function boot(): void
    {
        $integrationPath = $this->findIntegrationTestsPath();
        
        if ($integrationPath) {
            pest()->extend(IntegrationTestCase::class)
                ->use(DatabaseTruncation::class)
                ->in($integrationPath);
        }
    }

    /**
     * Finds the Integration tests directory path.
     * 
     * @return string|null The absolute path to the Integration directory, or null if not found
     */
    private function findIntegrationTestsPath(): ?string
    {
        $cwd = getcwd();
        
        // Try both 'tests' and 'Tests' directory names
        foreach (['tests', 'Tests'] as $testsDir) {
            $integrationPath = $cwd . DIRECTORY_SEPARATOR . $testsDir . DIRECTORY_SEPARATOR . 'Integration';
            
            if (is_dir($integrationPath)) {
                return realpath($integrationPath) ?: null;
            }
        }
        
        return null;
    }
}