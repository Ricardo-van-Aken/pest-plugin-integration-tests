<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginE2ETests;

use Pest\Contracts\Plugins\Bootable;
use RicardoVanAken\PestPluginE2ETests\E2ETestCase;
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
        $e2ePath = $this->findE2ETestsPath();
        
        if ($e2ePath) {
            pest()->extend(E2ETestCase::class)
                ->use(DatabaseTruncation::class)
                ->in($e2ePath);
        }
    }

    /**
     * Finds the E2E tests directory path.
     * 
     * @return string|null The absolute path to the E2E directory, or null if not found
     */
    private function findE2ETestsPath(): ?string
    {
        $cwd = getcwd();
        
        // Try both 'tests' and 'Tests' directory names
        foreach (['tests', 'Tests'] as $testsDir) {
            $e2ePath = $cwd . DIRECTORY_SEPARATOR . $testsDir . DIRECTORY_SEPARATOR . 'E2E';
            
            if (is_dir($e2ePath)) {
                return realpath($e2ePath) ?: null;
            }
        }
        
        return null;
    }
}