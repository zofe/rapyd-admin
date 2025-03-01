# Rapyd Admin - Auth Module

This is the auth module of [Rapyd Admin](https://github.com/zofe/rapyd-admin), a Laravel application bootstrap for your projects

It embed:

- login, registration, two factor authentication
- role/permission management
- user management
- impersonation features.
- component permissions, and priority "role based"


# Login / Registration & Two factor authentication

This module give you out of the box a fortify implementation https://laravel.com/docs/10.x/fortify#main-content 
with a bootstrap layout and default configuration you can customize on ./config/fortify.php 


# Permissions & Models

Two Models will be provided by this module:
 - Role
 - Permission

As you can imagine the role is a characteristic you can associate with a user, permissions are the "actions granted" for that role.

The module has a configuration file that allows you to define roles, permissions, and the relationship between them.

The editable configuration is provided in ./config/permission.php
Then you need to run the provided seeder.

these features are based on the library:
https://github.com/spatie/laravel-permission


# Impersonation 

One of the necessary features in the implementation of a backend is to impersonate other users/customers, this module has this functionality built in

This module include a trait `App\Modules\Auth\Traits\Impersonate` to check roles if user can impersonate other and to check if user can be impersonated.

By default, this trait add check if You are admin and the user you want to impersonate is not an admin (using roles).

for a custom implementation override `canImpersonate()` and `canBeImpersonated()` in your model

```php

use App\Modules\Auth\Traits\Impersonate;

class User extends Model
{
  use Impersonate;

```

this features is based on the library
https://github.com/lab404/laravel-impersonate



# Component roles & permissions



## Authorize trait

This module include a trait `App\Modules\Auth\Traits\Authorize` to check roles or permissions before build/render/execute component actions.

you can just include the trait, then add authorize check at booted time in your components:

```php

use App\Modules\Auth\Traits\Authorize;

class CompaniesEdit extends Component
{
    use Authorize;

    public function booted()
    {
        $this->authorize('admin|edit users');
    }
```

this will check if one of role or permission is applied to the logged-in user, otherwise it gives a permission error.

## Limit trait

This module include a trait `App\Modules\Auth\Traits\Limit` to add global scopes in your application, specific for role you need to jailroot eloquent models to specific query scopes. 


```php
<?php

namespace App\Modules\Companies\Livewire;

use App\Models\Company;
use App\Modules\Auth\Traits\Limit;
use Livewire\Component;

class CustomersTable extends Component
{
    use Limit;

    public function booted()
    {
        $this->limit();
    }


```

limit inside booted method will deal with all classes in Modules/*/Limits/LimitName.php
to add specific global scopes.
In the example below a global scope is added on Company model to be sure to bind queries on companies to those that are either the same as the logged-in user or are "daughters" of the one to which the logged-in user belongs.


```php
<?php

namespace App\Modules\Companies\Limits;

use App\Models\Company;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyLimit
{
    public static function limit($except = [])
    {
        $user = auth()->user();

        Company::addGlobalScope('onlyMine', function (Builder $builder) use (auth()->user()) {
            $builder->where('parent_id', $user->company_id)
                ->orWhere('id', $user->company_id)
        });
    }

}

```



# Component priority role based

This module include a middleware out of the box, that search for priority component instead of the one defaulted by the route.

By "priority component" we mean a component that is prefixed with the "Rolename" of the logged-in user
e.g., despite the defined route :

`Route::get('/companies/view/{company:id}', CompaniesView::class)`

`Customer` role user the middleware looks for `CustomerCompaniesView::class` and dispenses that if it exists.

this allows you to extend livewire components and adapt features and views potentially for each `role` to be managed (if you need to do so).

Just configure your prefixes in the config:
```php
    'role_to_component_prefix' => [
        'customer' => 'customer'
    ],
```

add the middleware in your app/Http/Kernel.php

```php
    protected $middlewareGroups = [
        'web' => [
            //..
            \App\Modules\Auth\Http\Middleware\ComponentByRole::class,
        ],
    ]
```


# Component role to component specific routes

assuming you're using Ticket open source module, 
you can customize that a custom role i.e. "customer" can open ticket using:


```php
    'role_to_component_class' => [
        'customer' => [
            'tickets.tickets.table' => \App\Components\Tickets\UserTicketsTable::class,
            'tickets.tickets.view' => \App\Components\Tickets\UserTicketsView::class
        ],

    ],
```

this way the routes `tickets.tickets.table` and `tickets.tickets.view` will be server by your custom implementation of components
(which can extend the default ticket module components)


# Installation & configuration 

This module is part of [Rapyd Admin](https://github.com/zofe/rapyd-admin) package
