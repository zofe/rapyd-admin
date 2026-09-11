<?php

return [
    'enabled' => env('RAPYD_LOG_ENABLED', true),

    'app' => [
        /*
         | How much of a log file is read, from its end. Daily files stay small; a
         | single laravel.log can grow to tens of MB, and only the tail is relevant.
         */
        'max_bytes' => env('RAPYD_LOG_MAX_BYTES', 2 * 1024 * 1024),

        // Parsed entries are cached per file (mtime + size), so filtering is instant.
        'cache_ttl' => 300,

        'per_page' => 50,
    ],

    'activity' => [
        'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

        // login, logout, failed attempts, impersonation start/stop
        'track_auth' => true,

        'per_page' => 50,

        // activitylog:clean removes older rows (schedule it in the app)
        'delete_records_older_than_days' => 180,

        // never shown in the activity table
        'hidden_properties' => ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'api_token'],
    ],
];
