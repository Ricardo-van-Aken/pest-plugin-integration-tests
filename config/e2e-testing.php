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

];

