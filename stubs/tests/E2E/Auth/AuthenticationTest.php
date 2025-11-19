<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $builder = $this->httpRequestBuilder();
    $response = $builder->get(route('login'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('users can authenticate using the login screen', function () {
    // Create a user without two factor enabled, and attempt to login
    $user = User::factory()->withoutTwoFactor()->create();
    
    $builder = $this->httpRequestBuilder();
    $response = $builder->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->send();

    // Verify user is redirected to the dashboard
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));

    // Verify user is authenticated by checking a protected route after refreshing the xsrf token
    $builder->refreshXsrf();
    $response = $builder->post('/test/requires-auth')->send();

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(['success' => true], json_decode($response->getBody(), true));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    // Create a user with two factor enabled
    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    // Attempt to login normally
    $builder = $this->httpRequestBuilder();
    $response = $builder->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->send();
    
    // Should redirect to two factor challenge
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('two-factor.login'), $response->getHeaderLine('Location'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    // Attempt to login with invalid password
    $builder = $this->httpRequestBuilder();
    $response = $builder->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->send();

    // Now try to access a protected route after refreshing the xsrf token
    $builder->refreshXsrf();
    $response = $builder->post('/test/requires-auth')->send();

    // Should redirect back to login with error
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('login'), $response->getHeaderLine('Location'));

});

test('users can logout', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Log in as the user
    $builder = $this->httpRequestBuilder()->actingAs($user);

    // Confirm user is authenticated
    $response = $builder->get('/test/requires-auth')->send();
    $this->assertEquals(200, $response->getStatusCode());

    // Logout the user
    $builder->post(route('logout'), [])->send();

    // Now try to access a protected route after refreshing the xsrf token
    $builder->refreshXsrf();
    $response = $builder->post('/test/requires-auth')->send();

    // Should redirect to login
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString('/login', $response->getHeaderLine('Location'));
});

test('users are rate limited', function () {
    $user =  User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder();
    
    // Make 5 failed login attempts to trigger rate limiting
    // Each failed attempt increments the rate limiter
    for ($i = 0; $i < 5; $i++) {
        $builder->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->send();
    }

    // The 6th attempt should be rate limited (429)
    // Guzzle throws an exception for 4xx responses, so we catch it and check the status code
    try {
        $response = $builder->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->send();
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $this->assertEquals(429, $e->getResponse()->getStatusCode());
    }
});

