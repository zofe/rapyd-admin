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
    //
    // user_attribute: which attribute of the local user is sent as X-Remote-User. Leave it out
    // when the two apps share their users (the local id is sent). Set it when the source has
    // accounts of its own and the local user carries its id — typically the OIDC `sub` saved at
    // login: 'user_attribute' => 'desk_sub'.
    'sources' => [
        // 'crm' => ['url' => env('REMOTE_CRM_URL'), 'token' => env('REMOTE_CRM_TOKEN')],
        // 'desk' => ['url' => env('REMOTE_DESK_URL'), 'token' => env('REMOTE_DESK_TOKEN'), 'user_attribute' => 'desk_sub'],
    ],

    // seconds a cached record is considered fresh
    'ttl' => env('RAPYD_REMOTE_TTL', 600),

    // seconds to wait on a call before giving up
    'timeout' => env('RAPYD_REMOTE_TIMEOUT', 3),

    // after a failure the source is left alone for this many seconds: without it every page
    // would pay a timeout while the source is down
    'retry_after' => env('RAPYD_REMOTE_RETRY_AFTER', 30),

    // shorter searches are not sent: they would match the whole list on every keystroke
    'min_search' => env('RAPYD_REMOTE_MIN_SEARCH', 2),
];
