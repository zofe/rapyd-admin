<?php

return [
    'enabled' => env('RAPYD_COMPANIES_ENABLED', true),

    /*
     | Roles available within a company.
     | Key = stored value in company_user.role
     | Value = display label
     | Projects can override this via config/rapyd/companies.php after publishing.
     */
    /*
     | Spatie role assigned to users created from within a company.
     */
    'user_role' => 'customer',

    'roles' => [
        'owner'  => 'Owner',
        'member' => 'Member',
    ],
];
