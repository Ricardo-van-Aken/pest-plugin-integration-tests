<?php

test('returns a successful response', function () {
    $response = $this->httpRequestBuilder()->get('/')->send();

    $this->assertEquals(200, $response->getStatusCode());
});
