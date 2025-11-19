<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password update page is displayed', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);

    $response = $builder->get(route('user-password.edit'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('password can be updated', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    
    // First visit the password edit page
    $builder->get(route('user-password.edit'))->send();

    $response = $builder->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->send();

    // Should redirect to password edit page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('user-password.edit'), $response->getHeaderLine('Location'));

    // Password should be updated
    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    
    // First visit the password edit page
    $builder->get(route('user-password.edit'))->send();

    $response = $builder->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->send();

    // Should redirect to password edit page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('user-password.edit'), $response->getHeaderLine('Location'));

    // Password should not be updated
    $this->assertFalse(Hash::check('new-password', $user->refresh()->password));
});

