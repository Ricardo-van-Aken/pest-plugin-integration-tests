<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginE2ETests;

use Pest\Plugin;
use PHPUnit\Framework\TestCase;

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

