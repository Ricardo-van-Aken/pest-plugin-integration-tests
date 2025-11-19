<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('two factor settings page can be rendered', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // NOTE: This feature has to be set when the application receives a request. Setting it here won't work.
    // Because this setting is default, this test will still succeed.
    // Features::twoFactorAuthentication([
    //     'confirm' => true,
    //     'confirmPassword' => true,
    // ]);

    $user = User::factory()->withoutTwoFactor()->create();
    $builder = $this->httpRequestBuilder()->actingAs($user);

    // Confirm the password before visiting the two factor page
    $builder->post(route('password.confirm'), [
        'password' => 'password',
    ])->send();

    $response = $builder->get(route('two-factor.show'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('two factor settings page requires password confirmation when enabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // NOTE: This feature has to be set when the application receives a request. Setting it here won't work.
    // Because this setting is default, this test will still succeed.
    // Features::twoFactorAuthentication([
    //     'confirm' => true,
    //     'confirmPassword' => true,
    // ]);

    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_recovery_codes' => encrypt(json_encode(['test-recovery-code-1', 'test-recovery-code-2'])),
    ])->save();
    
    $builder = $this->httpRequestBuilder()->actingAs($user, recoveryCode: 'test-recovery-code-1');

    $response = $builder->get(route('two-factor.show'))->send();

    // Should redirect to password confirmation page
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('password.confirm'), $response->getHeaderLine('Location'));
});

test('two factor settings page does not require password confirmation when disabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // NOTE: This feature has to be set when the application receives a request. Setting it here won't work.
    // Until changing configs for a single request is supported by the e2e testing package, we skip the test.
    $this->markTestSkipped('This e2e test is not yet supported.');
    // Features::twoFactorAuthentication([
    //     'confirm' => true,
    //     'confirmPassword' => false,
    // ]);

    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_recovery_codes' => encrypt(json_encode(['test-recovery-code-1', 'test-recovery-code-2'])),
    ])->save();
    
    $builder = $this->httpRequestBuilder()->actingAs($user, recoveryCode: 'test-recovery-code-1');

    $response = $builder->get(route('two-factor.show'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('two factor settings page returns forbidden response when two factor is disabled', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    // NOTE: This feature has to be set when the application receives a request. Setting it here won't work.
    // Until changing configs for a single request is supported by the e2e testing package, we skip the test.
    $this->markTestSkipped('This e2e test is not yet supported.');
    // config(['fortify.features' => []]);

    $user = User::factory()->create();
    $user->forceFill([
        'two_factor_recovery_codes' => encrypt(json_encode(['test-recovery-code-1', 'test-recovery-code-2'])),
    ])->save();
    
    $builder = $this->httpRequestBuilder()->actingAs($user, recoveryCode: 'test-recovery-code-1');

    $response = $builder->get(route('two-factor.show'))->send();

    $this->assertEquals(403, $response->getStatusCode());
});

