<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Redacted Keys
    |--------------------------------------------------------------------------
    |
    | These keys are removed from old_values and new_values, at any depth,
    | before an audit entry is stored. "*" matches any characters, so
    | "*_token" covers api_token, reset_token and so on.
    |
    */

    'redact' => [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'token',
        '*_secret',
        '*_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Entries older than this many days are pruned by the scheduled
    | "model:prune" command. Leave AUDIT_RETENTION_DAYS empty to keep
    | every entry forever.
    |
    */

    'retention_days' => env('AUDIT_RETENTION_DAYS'),

];
