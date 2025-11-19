<?php

use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

test('reset password link screen can be rendered', function () {
    $response = $this->httpRequestBuilder()
        ->get(route('password.request'))
        ->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('reset password link can be requested', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder();

    // Visit the password request page
    $builder->get(route('password.request'))->send();

    $response = $builder->post(route('password.email'), [
        'email' => $user->email,
    ])->send();

    // Should redirect back to the last page (password request page)
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('password.request'), $response->getHeaderLine('Location'));

    // Ensure a password reset entry exists for the user
    $this->assertTrue(
        DB::table('password_reset_tokens')->where('email', $user->email)->exists()
    );
});

test('reset password screen can be rendered', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    /** @var PasswordBroker $broker */
    $broker = Password::broker();
    $token = $broker->createToken($user);

    $response = $this->httpRequestBuilder()
        ->get(route('password.reset', $token))
        ->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('password can be reset with valid token', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    /** @var PasswordBroker $broker */
    $broker = Password::broker();
    $token = $broker->createToken($user);
    $builder = $this->httpRequestBuilder();

    // Visit the password reset page
    $builder->get(route('password.reset', $token))->send();

    $response = $builder->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->send();

    // Should redirect to login after successful reset
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('login'), $response->getHeaderLine('Location'));
    $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $builder = $this->httpRequestBuilder();

    // Visit the password reset page
    $builder->get(route('password.reset', 'invalid-token'))->send();

    $response = $builder->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->send();

    // Should redirect back to the password reset form
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('password.reset', 'invalid-token'), $response->getHeaderLine('Location'));
    $this->assertFalse(Hash::check('new-password', $user->fresh()->password));
});

