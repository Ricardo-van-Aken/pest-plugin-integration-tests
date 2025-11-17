<?php

test('registration screen can be rendered', function () {
    $response = $this->httpRequestBuilder()->get(route('register'))->send();

    $this->assertEquals(200, $response->getStatusCode());
});

test('new users can register', function () {
    $response = $this->httpRequestBuilder()
        ->withXsrf()
        ->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->send();

    $this->assertEquals(302, $response->getStatusCode());
    $this->assertStringContainsString(route('dashboard', absolute: false), $response->getHeaderLine('Location'));

    // Verify user is authenticated after registration
    $authResponse = $this->httpRequestBuilder()
        ->get('/test/requires-auth')
        ->send();

    $this->assertEquals(200, $authResponse->getStatusCode());
    $this->assertEquals(['success' => true], json_decode($authResponse->getBody(), true));
});

