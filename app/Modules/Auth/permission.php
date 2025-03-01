<?php

return [

    'models' => [
        'permission' => \App\Modules\Auth\Models\Permission::class,
        'role' => \App\Modules\Auth\Models\Role::class
    ],

    'table_names' => [

        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null, //default 'role_id',
        'permission_pivot_key' => null, //default 'permission_id',
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    'register_permission_check_method' => true,
    'teams' => false,
    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],

    'permissions' => [
        'view everything', 'edit everything', 'export everything',
        'view own business', 'edit own business',

        'view admins',  'edit admins',
        'view companies', 'edit companies', 'add companies', 'mod companies', 'del companies',

        'view users', 'edit users', 'add users', 'mod users', 'del users',
        'view own users', 'edit own users', 'add own users', 'mod own users', 'del own users',
    ],
    'roles' => [
        'admin',
        'operator',
        'customer'
    ],
    'role_permissions' => [
        'admin' => [
            'view everything', 'edit everything', 'export everything',
        ],
        'operator' => [
            'view companies', 'edit companies', 'add companies', 'mod companies', 'del companies',
            'view users', 'edit users', 'add users', 'mod users', 'del users',
        ],
        'customer' => [
            'view own business', 'edit own business',
            'view own users', 'edit own users', 'add own users', 'mod own users', 'del own users',
        ]
    ],
    'role_to_component_prefix' => [
    ],
    'role_to_component_fallback' => [
    ],
    'role_to_component_class' => [
    ],
];
