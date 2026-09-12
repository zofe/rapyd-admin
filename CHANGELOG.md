# Changelog

All notable changes to `rapyd-admin` will be documented in this file.

## [9.6.0] - 2026-09-12

### Added

- Themes: a theme is a folder of Blade layouts (`app`, `admin`, `frontend`, `auth`) plus compiled assets, registered by a ServiceProvider extending `Zofe\Rapyd\Themes\RapydThemeServiceProvider` and activated with `RAPYD_THEME`. The contract is in `docs/THEMES.md`; `php artisan rpd:theme:check` verifies it. Hooks `sidebar_footer`, `navbar_right`, `page_header`, `footer`.
- `config('rapyd.layout.*')` (brand, logo_sidebar, logo_login, favicon, custom_css); the old `config('layout.*')` keys are still read.
- `resources/sass/rapyd-base.scss`: the component styles alone, for external themes.
- Runtime palette: `RAPYD_PRIMARY`, `RAPYD_SIDEBAR_BG`, `RAPYD_SIDEBAR_TEXT`, `RAPYD_TOPBAR_BG`, `RAPYD_CONTENT_BG` (`config('rapyd.layout.palette')`) change the colours without recompiling; the components read Bootstrap's CSS variables (`resources/sass/_tokens.scss`).

### Changed

- One layout name for every module: `layout::admin` (the `auth::admin`, `companies::admin`, `log::admin` wrappers are gone). The auth pages extend `layout::auth`, styled by the theme instead of a CDN Bootstrap.

- Assets are built with Vite instead of laravel-mix (`npm run dev` / `npm run build` from the package root, see "Styles and scripts" in the README). Same output files, no manifest; `public/fonts/` replaces `public/fonts/vendor/bootstrap-icons/`.

### Fixed

- Dark mode: tables were black-on-dark because Bootstrap 5.3 colours cells through `--bs-table-*` variables. The theme switcher now also sets `data-bs-theme="dark"` (native Bootstrap dark mode for tables, forms, dropdowns, modals) and the theme maps its dark palette onto Bootstrap's semantic variables (`--bs-emphasis-color`, `--bs-secondary-color`, `--bs-table-*`).
- `layout/navs/_sidebar.scss` and `_topbar.scss` were identical copies of `layout/_sidebar.scss` / `_topbar.scss`, compiled twice: removed.
- `rapyd.js` no longer throws on pages without Livewire (the login page): `livewire-sortable` is registered on `livewire:init` instead of at import time, so the theme switcher and modals initialise everywhere.

### Removed

- Leftovers of the old standalone `zofe/auth-module` and `zofe/layout-module` packages inside `app/Modules/{Auth,Layout}`: the unregistered `rpd:make:auth` command and its User stub, `DatabaseSeederTests`, per-module `.gitignore`, `LICENSE.md` and test folders that no suite ran.
- `app/Modules/Layout` inside the package: a stale, divergent copy of the bundled Layout module that nothing loaded (`rpd:eject` copies from `src`).
- The second Node toolchain in `app/Modules/Layout` (`package.json`, lock, `webpack.mix.js`, `resources/`): the layout SCSS lives in `resources/sass/layout`. Unused dev dependencies (axios, lodash, moment, popper.js) and the dead `resources/js/alpine.js` / `livewire.esm.js` sources.

## [9.5.0] - 2026-09-11

### Breaking changes

- **One company per user.** `users.company_id` + `users.company_role` (`owner|member`) replace the `company_user` pivot; the migration `2026_09_11_000001_drop_company_user_table` moves the primary (or only) membership onto `users` and drops the pivot. `HasCompanies` now exposes `company()`, `isOwner()`, `isOwnerOf()`, `belongsToCompany()` and `assignToCompany($company, $role)`; `attachToCompany()`, `primaryCompany()`, `companies()` are gone. `Company::users()` is a `hasMany`.

### Added

- Auth users pages show and assign the company: Company column in the table, Company card in the view, Company / Role in company selects in the edit form (limited to the companies the viewer can see; only `rapyd.auth.super_admin_roles` can move an existing user).
- `tests/Feature/AuthUsersTest`.

### Changed

- `UsersTable`, `UsersView` and `UsersEdit` use `config('auth.providers.users.model')` instead of `App\Models\User` (`Route::model('user', …)` in the Auth routes).
- `x-rpd::input/select/select-list/rich-text` accept array validation rules when detecting `required`.
- The package test case boots the `laravel-impersonate` and `laravel-disposable-email` providers.

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
