<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Spatie Permission — Models & Tables
    |--------------------------------------------------------------------------
    */
    'models' => [
        'permission' => \Zofe\Rapyd\Modules\Auth\Models\Permission::class,
        'role'       => \Zofe\Rapyd\Modules\Auth\Models\Role::class,
    ],

    'table_names' => [
        'roles'                 => 'roles',
        'permissions'           => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles'       => 'model_has_roles',
        'role_has_permissions'  => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key'       => null,
        'permission_pivot_key' => null,
        'model_morph_key'      => 'model_id', // UUID-compatible
        'team_foreign_key'     => 'team_id',
    ],

    'register_permission_check_method' => true,
    'teams'                            => false,
    'display_permission_in_exception'  => false,
    'display_role_in_exception'        => false,
    'enable_wildcard_permission'       => false,

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key'             => 'spatie.permission.cache',
        'store'           => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions
    |--------------------------------------------------------------------------
    | These are the defaults. Override in your project's config/permission.php.
    |
    | Tier roles (tier1, tier2, tier3) map to the tiers configured in rapyd.php.
    | They are created with these names and their labels come from rapyd.companies.tier_labels.
    */
    'permissions' => [
        'view everything', 'edit everything', 'export everything',
        'view own business', 'edit own business',
        'view companies', 'edit companies', 'add companies', 'del companies',
        'view users', 'edit users', 'add users', 'del users',
        'view own users', 'edit own users', 'add own users', 'del own users',
        'view own addresses', 'edit own addresses',
    ],

    'roles' => [
        'admin',
        'operator',
        'tier1',
        'tier2',
    ],

    'role_permissions' => [
        'admin' => [
            'view everything', 'edit everything', 'export everything',
        ],
        'operator' => [
            'view companies', 'edit companies', 'add companies', 'del companies',
            'view users', 'edit users', 'add users', 'del users',
        ],
        'tier1' => [
            'view own business', 'edit own business',
            'view own users', 'edit own users', 'add own users', 'del own users',
            'view own addresses', 'edit own addresses',
            'view companies',
        ],
        'tier2' => [
            'view own business', 'edit own business',
            'view own users', 'edit own users',
            'view own addresses', 'edit own addresses',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Role → Component prefix mapping
    |--------------------------------------------------------------------------
    | Used by ComponentByRole middleware to load role-specific Livewire views.
    | e.g. 'tier1' => 'partner' means views/partner/xxx.blade.php for tier1 users.
    */
    'role_to_component_prefix' => [],

];
