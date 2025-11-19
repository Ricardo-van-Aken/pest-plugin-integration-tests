<?php

test('registration screen can be rendered', function () {
    $response = $this->httpRequestBuilder()->get(route('register'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('new users can register', function () {
    $builder = $this->httpRequestBuilder();
    $response = $builder->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->send();

    // Should redirect to dashboard after registration
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));

    // Verify user is authenticated after registration
    $response = $builder->get('/test/requires-auth')->send();

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals(['success' => true], json_decode($response->getBody(), true));
});

