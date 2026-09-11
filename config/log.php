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
];
