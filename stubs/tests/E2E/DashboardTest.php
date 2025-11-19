<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    // Verify guest cannot access protected route
    $response = $this->httpRequestBuilder()
        ->get('/test/requires-auth')
        ->send();

    // Should redirect to login
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('login'), $response->getHeaderLine('Location'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->get(route('dashboard'))->send();

    
    $this->assertEquals(200, $response->getStatusCode());
});

