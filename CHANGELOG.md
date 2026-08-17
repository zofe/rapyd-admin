# Changelog

All notable changes to `rapyd-admin` will be documented in this file.

## [1.0.0] - 2026-08-17

### Breaking changes

- **Livewire 4 required.** `livewire/livewire ^4.0` is now the minimum. All `wire:model.lazy` usages have been migrated to `wire:model.blur`.
- **Auth and Layout are now bundled modules.** The separate packages `zofe/auth-module` and `zofe/layout-module` are no longer required and have been archived. Their functionality ships directly in `zofe/rapyd-admin`.
- **Companies module is now bundled.** Multi-tenant company support ships in core; enable it via `config('rapyd.companies.enabled', true)`.
- **`rapyd-module-installer` removed.** The installer package and its mechanism have been replaced by the new `rpd:install` command.
- **Livewire component registration changed.** Third-party modules must call `Livewire::addNamespace()` in their ServiceProvider instead of `Livewire::component()`. The `::` namespace separator is required for component names.
- **PHP 8.2+ required.** Laravel 13 additionally requires PHP 8.3.

### Added

- `rpd:install` — publishes config files, injects `HasRoles`/`HasCompanies` traits into the User model, and publishes Spatie permission migrations.
  - `--uuid-users` flag: publishes a UUID conversion migration for `users.id`.
  - `--companies` flag: also injects `HasCompanies` into User.
- `rpd:eject {Module}` — copies a bundled module (`Auth`, `Layout`, `Companies`) to `app/Modules/` for full customization. Views, routes and migrations are loaded automatically; no `composer dump-autoload` needed.
- Bundled `Auth` module: Fortify-based login, registration, password reset, 2FA, email verification views and routes.
- Bundled `Layout` module: Bootstrap 5.3 admin sidebar, dark-mode toggle, navbar.
- Bundled `Companies` module: multi-tenant company hierarchy (1–3 tiers), `CompanyAuth`, `CompanyLimit`, `CompaniesSeeder`.
- `CompaniesSeeder` seeds a platform company and a demo tenant based on `RPD_TIERS` config.
- CI matrix: PHP 8.2/8.3 × Laravel 12/13 (testbench 10.* for Laravel 12, testbench 11.* for Laravel 13).

### Changed

- `rpd:make:setup` now calls `rpd:install` followed by `migrate` and the module seeders (was `rpd:make:auth`).
- `ModuleServiceProvider` uses `Livewire::addNamespace()` instead of the deprecated `registerComponentDirectory()`.
- `RapydServiceProvider` adds a `resolveMissingComponent()` fallback for dotted component names.

### Removed

- Dependency on `zofe/rapyd-module-installer`.
- `rpd:make:auth` command (replaced by `rpd:install`).

## [0.12.17] and earlier

See the [GitHub releases page](https://github.com/zofe/rapyd-admin/releases) for the 0.12.x and earlier history.
