<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->get(route('verification.notice'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );
    $builder = $this->httpRequestBuilder()->actingAs($user);
    
    // Now try builder method to compare
    $response = $builder->get($verificationUrl)->send();

    // Should redirect to the dashboard with the verified flag
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));

    // Email should be verified
    $this->assertTrue($user->fresh()->hasVerifiedEmail());
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $builder = $this->httpRequestBuilder()->actingAs($user);
    try {
        $builder->get($verificationUrl)->send();
        $this->fail('Expected request to throw a ClientException for invalid hash.');
    } catch (\GuzzleHttp\Exception\ClientException $exception) {
        $response = $exception->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    $this->assertFalse($user->fresh()->hasVerifiedEmail());
});

test('email is not verified with invalid user id', function () {
    $user = User::factory()->unverified()->withoutTwoFactor()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id + 1000, 'hash' => sha1($user->email)]
    );

    $builder = $this->httpRequestBuilder()->actingAs($user);
    try {
        $builder->get($verificationUrl)->send();
        $this->fail('Expected request to throw a ClientException for invalid user id.');
    } catch (\GuzzleHttp\Exception\ClientException $exception) {
        $response = $exception->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    $this->assertFalse($user->fresh()->hasVerifiedEmail());
});

test('verified user is redirected to dashboard from verification prompt', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->get(route('verification.notice'))->send();

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));
});

test('already verified user visiting verification link is redirected without changes', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->get($verificationUrl)->send();

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));
    $this->assertTrue($user->fresh()->hasVerifiedEmail());
});

