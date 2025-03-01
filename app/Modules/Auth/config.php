<?php

/*
|--------------------------------------------------------------------------
| Auth Module Configurations
|--------------------------------------------------------------------------
|
|
*/
return [
    'layout' => 'auth::admin',
    'menu_admin' => 'auth::admin_menu',
    'menu_admin_position' => 0,

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
