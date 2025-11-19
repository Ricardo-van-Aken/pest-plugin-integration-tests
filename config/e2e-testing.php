<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Testing Header Name
    |--------------------------------------------------------------------------
    |
    | The HTTP header name that triggers the database switch.
    |
    */

    'header_name' => env('TESTING_DB_HEADER', 'X-TESTING'),

    /*
    |--------------------------------------------------------------------------
    | Login Route
    |--------------------------------------------------------------------------
    |
    | The route used for user authentication in integration tests. This can be a full URL or a route name.
    |
    */

    'login_route' => env('TESTING_DB_LOGIN_ROUTE', 'login.store'),

    /*
    |--------------------------------------------------------------------------
    | Two Factor Challenge Route (POST)
    |--------------------------------------------------------------------------
    |
    | The route used for submitting the 2FA challenge (recovery code or TOTP code).
    | This can be a full URL or a route name. Defaults to Laravel Fortify's route.
    |
    */

    'two_factor_challenge_route' => env('TESTING_DB_TWO_FACTOR_CHALLENGE_ROUTE', 'two-factor.login'),

    /*
    |--------------------------------------------------------------------------
    | Two Factor Challenge Location Route (GET)
    |--------------------------------------------------------------------------
    |
    | The route used to check if login redirected to 2FA challenge page.
    | This can be a full URL or a route name. Defaults to Laravel Fortify's route.
    |
    */

    'two_factor_challenge_location_route' => env('TESTING_DB_TWO_FACTOR_CHALLENGE_LOCATION_ROUTE', 'two-factor.login'),

];

