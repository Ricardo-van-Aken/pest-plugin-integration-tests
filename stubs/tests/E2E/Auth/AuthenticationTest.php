<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->httpRequestBuilder()->get(route('login'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->httpRequestBuilder()
        ->withXsrf()
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->send();

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));

    // Verify user is authenticated by checking a protected route
    $authResponse = $this->httpRequestBuilder()
        ->get('/test/requires-auth')
        ->send();

    $this->assertEquals(200, $authResponse->getStatusCode());
    $this->assertEquals(['success' => true], json_decode($authResponse->getBody(), true));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->httpRequestBuilder()
        ->withXsrf()
        ->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->send();

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('two-factor.login'), $response->getHeaderLine('Location'));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->httpRequestBuilder()
        ->withXsrf()
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->send();

    // Should redirect back to login with error
    $this->assertEquals(302, $response->getStatusCode());

    // Verify user is not authenticated
    $authResponse = $this->httpRequestBuilder()
        ->get('/test/requires-auth')
        ->send();

    // Should redirect to login (401 or redirect)
    $this->assertNotEquals(200, $authResponse->getStatusCode());
});

test('users can logout', function () {
    $user = User::factory()->create();

    // First verify user is authenticated
    $authResponse = $this->httpRequestBuilder()
        ->actingAs($user)
        ->get('/test/requires-auth')
        ->send();

    $this->assertEquals(200, $authResponse->getStatusCode());

    // Now logout
    $response = $this->httpRequestBuilder()
        ->actingAs($user)
        ->post(route('logout'))
        ->send();

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('home'), $response->getHeaderLine('Location'));

    // Verify user is no longer authenticated
    $authResponseAfterLogout = $this->httpRequestBuilder()
        ->get('/test/requires-auth')
        ->send();

    // Should redirect to login (401 or redirect)
    $this->assertNotEquals(200, $authResponseAfterLogout->getStatusCode());
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->httpRequestBuilder()
        ->withXsrf()
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
        ->send();

    $this->assertEquals(429, $response->getStatusCode());
});

