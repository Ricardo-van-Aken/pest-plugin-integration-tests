<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginIntegrationTests;

use Pest\Plugin;
// use RicardoVanAken\PestPluginIntegrationTests\Example;
use RicardoVanAken\PestPluginIntegrationTests\IntegrationTestCase;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Testing\DatabaseTruncation;

Plugin::uses(Advice::class);

/**
 * @return TestCase
 */
function example(string $argument)
{
    return test()->example(...func_get_args()); // @phpstan-ignore-line
}

/**
 * Get a random piece of bad advice.
 */
function badAdvice(): string
{
    return test()->giveBadAdvice(); // @phpstan-ignore-line
}

/**
 * Get a random piece of good advice.
 */
function goodAdvice(): string
{
    return test()->giveGoodAdvice(); // @phpstan-ignore-line
}

/**
 * Get a random piece of advice (good or bad).
 */
function advice(): string
{
    return test()->giveAdvice(); // @phpstan-ignore-line
}

# Example for testing
// pest()->preset('ddd', function () {
//     return [
//         expect('Infrastructure')->toOnlyBeUsedIn('Application'),
//         expect('Domain')->toOnlyBeUsedIn('Application'),
//     ];
// });
// Modern Pest 4.0 API for arch presets
pest()->presets()->custom('ddd', function () {
    return [
        expect('Infrastructure')->toOnlyBeUsedIn('Application'),
        expect('Domain')->toOnlyBeUsedIn('Application'),
    ];
});

// Extend tests in the 'Integration' directory to use IntegrationTestCase
// pest()->extend(IntegrationTestCase::class)
//     ->use(DatabaseTruncation::class)
//     ->in('Integration');

