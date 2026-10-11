<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Remote (experimental: the API may change)
    |--------------------------------------------------------------------------
    | Show entities owned by another app without copying them. One app exposes
    | a resource (RemoteExpose::resource), the others read it with Remote::find,
    | HasRemote and the <x-remote::select|checkboxes|label> components.
    | Off by default: no route, no migration until enabled.
    */
    'enabled' => env('RAPYD_REMOTE_ENABLED', false),

    // Provider: apps allowed to read from this one (name => shared token)
    'callers' => [
        // 'portal' => env('REMOTE_TOKEN_PORTAL'),
    ],

    // Consumer: apps this one reads from ("crm.customers" = source crm, resource customers)
    'sources' => [
        // 'crm' => ['url' => env('REMOTE_CRM_URL'), 'token' => env('REMOTE_CRM_TOKEN')],
    ],

    // seconds a cached record is considered fresh
    'ttl' => env('RAPYD_REMOTE_TTL', 600),
];
