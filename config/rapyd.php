<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    | Populated at runtime by ModuleServiceProvider. Do not set manually.
    */
    'modules' => [],
    'menus'   => [],

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    'users' => [
        'uuid' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Companies / Multi-tenancy
    |--------------------------------------------------------------------------
    | tiers: 1 = flat (all companies equal)
    |        2 = two levels: tier1 sees own + children
    |        3 = three levels: tier1 → tier2 → tier3
    |
    | tier_labels: UI labels only — code always uses tier1/tier2/tier3.
    | Configure via .env: RPD_TIERS=2, RPD_TIER1_LABEL=partner, RPD_TIER2_LABEL=customer
    */
    'companies' => [
        'enabled'     => true,
        'uuid'        => true,
        'tiers'       => env('RPD_TIERS', 1),
        'tier_labels' => [
            'tier1' => env('RPD_TIER1_LABEL', 'customer'),
            'tier2' => env('RPD_TIER2_LABEL', 'reseller'),
            'tier3' => env('RPD_TIER3_LABEL', 'partner'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth / Roles
    |--------------------------------------------------------------------------
    | super_admin_roles: bypass all global scopes (Authorize + Limit).
    | Roles and permissions are defined in config/permission.php.
    */
    'auth' => [
        'super_admin_roles' => ['admin'],
    ],

];
