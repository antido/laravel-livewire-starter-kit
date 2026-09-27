<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers
    |--------------------------------------------------------------------------
    |
    | Sent by App\Http\Middleware\SecurityHeaders. On by default in production
    | only, because the Content-Security-Policy would block the Vite dev server
    | (http://localhost:5173) during local development.
    |
    */

    'headers' => in_array(env('SECURITY_HEADERS'), [null, ''], true)
        ? env('APP_ENV') === 'production'
        : (bool) env('SECURITY_HEADERS'),

    /*
    |--------------------------------------------------------------------------
    | Strict-Transport-Security
    |--------------------------------------------------------------------------
    |
    | Only turn this on once the whole site is served over HTTPS: browsers
    | then refuse plain HTTP for the given time. Sent on HTTPS requests only.
    |
    */

    'hsts' => (bool) env('SECURITY_HSTS', false),

    'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),

];
