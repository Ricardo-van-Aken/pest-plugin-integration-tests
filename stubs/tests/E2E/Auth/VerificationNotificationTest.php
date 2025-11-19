<?php

use App\Models\User;

test('sends verification notification for unverified users', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'email_verified_at' => null,
    ]);

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->post(route('verification.send'))->send();

    // Should redirect to the home page with a success status
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('home'), $response->getHeaderLine('Location'));
    $this->assertNull($user->fresh()->email_verified_at);
});

test('verified users are redirected to the dashboard when requesting a verification notification', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'email_verified_at' => now(),
    ]);

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->post(route('verification.send'))->send();

    // Already verified users should be redirected to the dashboard
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));
    $this->assertNotNull($user->fresh()->email_verified_at);
});

