<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->get(route('password.confirm'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('password confirmation requires authentication', function () {
    $response = $this->httpRequestBuilder()
        ->get(route('password.confirm'))
        ->send();

    // Guests should be redirected to login
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('login'), $response->getHeaderLine('Location'));
});

