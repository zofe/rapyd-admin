# Auth

Login, registration, password reset, email verification and two-factor authentication come from Laravel Fortify,
with Bootstrap views rendered by the theme. Roles and permissions come from `spatie/laravel-permission`, configured
in one file and seeded. Google sign-in is built in. Impersonation too.

## Setup

`php artisan rpd:make:setup` runs `rpd:install`, migrates and seeds. What `rpd:install` does:

- publishes `config/rapyd.php`, `config/permission.php` and the Spatie migrations;
- adds the `HasRoles`, `Authorize`, `Limit`, `Impersonate`, `ShortId`, `SSearch` traits to `app/Models/User.php`
  (`--companies` adds `HasCompanies`, `--addresses` adds `HasAddresses`, `--uuid-users` publishes the UUID conversion);
- `--no-user-model` skips the User model, `--force` overwrites published configs.

The seeder creates the roles, permissions and the first admin (`admin@laravel` / `admin`: change it).

## Roles and permissions

Declared in `config/permission.php`, applied by `AuthSeeder`:

```php
'permissions' => ['view everything', 'edit everything', 'view users', 'edit users', 'view own business', /* … */],
'roles'       => ['admin', 'operator', 'customer'],
'role_permissions' => [
    'admin'    => ['view everything', 'edit everything', 'export everything'],
    'operator' => ['view companies', 'edit companies', 'view users', 'edit users', 'view logs'],
    'customer' => ['view own business', 'edit own business', 'view own users', 'edit own users'],
],
```

Add your own permissions there and re-run the seeder. Roles listed in `config('rapyd.auth.super_admin_roles')`
(default `['admin']`) bypass every `Limit` and `Authorization`.

In a Livewire component:

```php
$this->authorize('admin|edit users');            // any of these roles or permissions, else 403
$this->authorize('admin|edit companies', $company); // plus the Authorization classes on $company, else 404
```

In Blade: `Auth::user()->hasRoleOrPermission('admin|view users')`.

The Users, Roles & Permissions pages are at `/auth/users` and `/auth/permissions`.

## Google sign-in

```dotenv
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT=https://your-app.test/google/auth/callback
```

The "Sign in with Google" button appears on the login page; without `GOOGLE_CLIENT_ID` the Google routes are not
registered at all. New users are created on first login; `google_id` and `avatar` are stored on the user.

## Impersonation

Users with the `admin` role see a "login as" icon in the users table (`lab404/laravel-impersonate`); the topbar
shows "Leave impersonation" while impersonating.

## Customising

- Views: `auth::auth.login`, `register`, `forgot-password`, `reset-password`, `two-factor-challenge`, `verify-email`
  extend `layout::auth`; publish them to `resources/views/vendor/auth` or provide an `auth.blade.php` in a theme.
- Fortify features and rate limits: `config/fortify.php` (published by `rpd:make:setup`).
- Deep changes: `php artisan rpd:eject Auth`.
