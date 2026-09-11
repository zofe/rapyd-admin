# Rapyd Admin - Search Module

This is the search module of [Rapyd Admin](https://github.com/zofe/rapyd-admin), a Laravel application bootstrap for your projects

It embed:

- global search dependencies: laravel scout (meilisearch/algolia/typesense)
- search widgets integrated in Rapyd Admin frontend/admin


# Installation & configuration 


By default, this "field" search in a list of models you can define in the config file,
for each model you need to specify:

* the class
* the query scope
* the route (to link at result detail)
* the query limit
* the view (of the result item, to customize item appareance)


```php
    'models' => [
        [
            'class' => \App\Models\User::class,
            'scope' => 'ssearch',
            'route' => 'auth.users.view',
            'limit' => 5,
            'view'  => 'search::item',
        ],
    ]
```


# Usage
This command will create a folder "Search" in your /app/Modules/ folder.

this component: 
`/app/Modules/Search/Livewire/SearchNavbar.php`

has the logic of "global search" in the project.
searches the users, and potentially the main models of all other installed modules.


# Installation & configuration

This module is part of [Rapyd Admin](https://github.com/zofe/rapyd-admin) package
