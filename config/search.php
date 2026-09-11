<?php

return [
    'enabled' => env('RAPYD_SEARCH_ENABLED', true),

    /*
     | When true, models using Zofe\Rapyd\Traits\SSearch that also use Laravel Scout
     | query the search engine (e.g. Meilisearch) first; otherwise a LIKE on
     | $searchableColumns is used. Requires laravel/scout and a SCOUT_DRIVER.
     */
    'use_scout' => env('RAPYD_SEARCH_USE_SCOUT', false),

    /*
     | Models searched by the navbar. Entries whose route does not exist are skipped.
     |   class : model exposing the scope (SSearch provides ssearch($term, $limit))
     |   route : detail route receiving the model key
     |   label : attribute shown in the result
     |   icon  : Font Awesome icon name
     */
    'models' => [
        [
            'class' => \App\Models\User::class,
            'scope' => 'ssearch',
            'route' => 'auth.users.view',
            'label' => 'name',
            'icon'  => 'user',
            'limit' => 5,
        ],
        [
            'class' => \Zofe\Rapyd\Modules\Companies\Models\Company::class,
            'scope' => 'ssearch',
            'route' => 'companies.view',
            'label' => 'business_name',
            'icon'  => 'building',
            'limit' => 5,
        ],
    ],
];
