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
        // Automatically extend tests in 'Integration' directory with IntegrationTestCase
        pest()->extend(IntegrationTestCase::class)
            ->use(DatabaseTruncation::class)
            ->in('Integration');
    }
}
 