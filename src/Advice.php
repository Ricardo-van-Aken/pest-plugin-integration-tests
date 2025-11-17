<?php

declare(strict_types=1);

namespace RicardoVanAken\PestPluginE2ETests;

/**
 * @internal
 */
trait Advice // @phpstan-ignore-line
{
    /**
     * @var array<string>
     */
    private static array $badAdvices = [
        'Just use var_dump() everywhere for debugging.',
        'Hardcode all values, configuration is overrated.',
        'Ignore error handling, it will work fine.',
        'Copy-paste code from Stack Overflow without understanding it.',
        'Use global variables everywhere for state management.',
        'Skip writing tests, you can test it manually.',
        'Use eval() to execute user input, it\'s fine.',
        'Never update dependencies, they might break things.',
        'Use sleep() to fix race conditions.',
        'Just suppress all errors with @.',
        'Use goto statements for better code flow.',
        'Store passwords in plain text, encryption is complicated.',
        'Make everything public, encapsulation is unnecessary.',
        'Use magic numbers instead of constants.',
        'Never refactor, the code works as-is.',
    ];

    /**
     * @var array<string>
     */
    private static array $goodAdvices = [
        'Write tests for your code to catch bugs early.',
        'Use meaningful variable and function names.',
        'Handle errors gracefully with proper exception handling.',
        'Keep functions small and focused on a single responsibility.',
        'Use dependency injection for better testability.',
        'Document your code with clear comments and docblocks.',
        'Follow the DRY (Don\'t Repeat Yourself) principle.',
        'Use version control and commit frequently with clear messages.',
        'Review code before merging to catch issues early.',
        'Use type hints and return types for better code clarity.',
        'Refactor code regularly to improve maintainability.',
        'Use design patterns when appropriate, but don\'t over-engineer.',
        'Write self-documenting code that explains itself.',
        'Keep dependencies up to date for security and features.',
        'Use constants or configuration files instead of magic numbers.',
    ];

    public function giveBadAdvice(): string
    {
        return self::$badAdvices[array_rand(self::$badAdvices)];
    }

    public function giveGoodAdvice(): string
    {
        return self::$goodAdvices[array_rand(self::$goodAdvices)];
    }

    public function giveAdvice(): string
    {
        $isGood = (bool) random_int(0, 1);
        
        return $isGood 
            ? self::$goodAdvices[array_rand(self::$goodAdvices)]
            : self::$badAdvices[array_rand(self::$badAdvices)];
    }
}
