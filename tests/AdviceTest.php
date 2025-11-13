<?php

use function RicardoVanAken\PestPluginIntegrationTests\badAdvice;
use function RicardoVanAken\PestPluginIntegrationTests\goodAdvice;
use function RicardoVanAken\PestPluginIntegrationTests\advice;

test('can get bad advice', function () {
    $advice = badAdvice();
    
    expect($advice)->toBeString()
        ->not->toBeEmpty();
});

test('can get good advice', function () {
    $advice = goodAdvice();
    
    expect($advice)->toBeString()
        ->not->toBeEmpty();
});

test('can get random advice', function () {
    $advice = advice();
    
    expect($advice)->toBeString()
        ->not->toBeEmpty();
});

test('can get advice from trait methods', function () {
    $badAdvice = $this->giveBadAdvice();
    $goodAdvice = $this->giveGoodAdvice();
    $randomAdvice = $this->giveAdvice();
    
    expect($badAdvice)->toBeString()
        ->not->toBeEmpty();
    expect($goodAdvice)->toBeString()
        ->not->toBeEmpty();
    expect($randomAdvice)->toBeString()
        ->not->toBeEmpty();
});
