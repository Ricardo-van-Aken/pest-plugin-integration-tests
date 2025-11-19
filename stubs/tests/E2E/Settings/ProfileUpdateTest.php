<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->get(route('profile.edit'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('profile information can be updated', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Make sure email is verified
    $this->assertNotNull($user->email_verified_at);

    $newName = fake()->name();
    $newEmail = fake()->unique()->safeEmail();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->patch(route('profile.update'), [
        'name' => $newName,
        'email' => $newEmail,
    ])->send();

    // Should redirect to profile edit page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('profile.edit'), $response->getHeaderLine('Location'));

    // User should be updated
    $user->refresh();
    $this->assertSame($newName, $user->name);
    $this->assertSame($newEmail, $user->email);

    // Email verification status should be reset to null (since we updated the email)
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Make sure email is verified	
    $emailVerifiedAt = $user->email_verified_at;
    $this->assertNotNull($emailVerifiedAt);

    $newName = fake()->name();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->patch(route('profile.update'), [
        'name' => $newName,
        'email' => $user->email,
    ])->send();

    // Should redirect to profile edit page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('profile.edit'), $response->getHeaderLine('Location'));

    // Email verification status should be unchanged, while name should be updated
    $this->assertEquals($emailVerifiedAt, $user->refresh()->email_verified_at);
    $this->assertSame($newName, $user->name);
});

test('user can delete their account', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    $response = $builder->delete(route('profile.destroy'), [
        'password' => 'password',
    ])->send();

    // Should redirect to home page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('home'), $response->getHeaderLine('Location'));

    // User should be deleted
    $this->assertNull(User::find($user->id));
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $builder = $this->httpRequestBuilder()->actingAs($user);
    
    // First visit the profile page
    $builder->get(route('profile.edit'))->send();

    $response = $builder->delete(route('profile.destroy'), [
        'password' => 'wrong-password',
    ])->send();

    // Should redirect to profile edit page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('profile.edit'), $response->getHeaderLine('Location'));

    // User should not be deleted
    $this->assertNotNull(User::find($user->id));
});

