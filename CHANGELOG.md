# Changelog

All notable changes to `rapyd-admin` will be documented in this file.

## [9.14.0] - 2026-09-18

### Added

- For the agents of an application: a Laravel Boost guideline (`resources/boost/guidelines/core.blade.php`) and two
  skills, `rapyd-module` and `rapyd-workflow` (`resources/boost/skills`), picked up by `boost:install` / `boost:update`;
  `php artisan rpd:ai` installs them without Boost (`.claude/skills`, `AGENTS.md` between markers, `@AGENTS.md` in `CLAUDE.md`).
- `docs/MODULES.md`: a Workflows section (the workflow name is the model's morph alias); `docs/AI.md`: guideline and skills.

### Fixed

- Docs and README taught `rpd:make all Model`, which generates an `AllTable` component: the syntax is
  `rpd:make Articles Article --module=Blog`. `rpd:context` extension patterns updated (modules are discovered, scoping
  is `$this->limit()`, workflows); the bundled modules list was three of seven.

## [9.13.4] - 2026-09-17

### Added

- `x-rpd::modal`: `actionLabel` prop for the submit button (default "Confirm").

## [9.13.3] - 2026-09-16

### Fixed

- `x-rpd::metadata` rendered with Tailwind classes: Bootstrap input groups now, key / value bound to an array property.
- The utilities the module views rely on (`border-bottom-except-last`, `flex-center-end`, `text-gray-*`) moved into `rapyd-base`, so external themes get them too (the price list rows had no separators in Tabler / Sneat).

## [9.13.2] - 2026-09-15

### Changed

- `RAPYD_AUTH_LINKS=false` also hides the Login link a guest sees in the admin navbar (public demo pages).

## [9.13.1] - 2026-09-15

### Added

- `rapyd.theme_picker` (`RAPYD_THEME_PICKER`, default `true`): `false` hides the palette icon in the navbars; `?rapyd_theme=<name>` links keep switching.

## [9.13.0] - 2026-09-15

### Added

- Per-visitor theme switch (`RAPYD_THEME_SWITCH=true`): a palette icon in the navbar lists the bundled look and the registered themes; `?rapyd_theme=<name>` keeps the choice in the session (`Zofe\Rapyd\Themes\ThemeManager`, `ThemeBySession` middleware on the `web` group, `layout::includes.theme_picker`).

## [9.12.1] - 2026-09-14

### Changed

- Frontend navbar: the light / dark toggle is shown to guests too (it was only in the logged-in navbar).
- `rapyd.layout.auth_links` (`RAPYD_AUTH_LINKS`, default `true`): set to `false` to hide the Login / Register links of the frontend navbar; the routes stay.

### Fixed

- A published `config/rapyd.php` older than the package no longer drops the `layout` keys added since: they get the package defaults (read from `.env`).

## [9.12.0] - 2026-09-13

### Changed

- Address form with a lookup service: city, country and state are read-only and come from the chosen suggestion (a consolidated address); street, number and postcode stay editable.
- `state_code` from Google: the ISO 3166-2 subdivision, the first level when it is a code (US-CA), else the second (IT-MI).

### Fixed

- `x-rpd::select-list` did not show a value changed on the server (the select lives in `wire:ignore`): TomSelect now follows the Livewire property.

## [9.11.2] - 2026-09-13

### Fixed

- Address search: the suggestions list has an explicit surface colour (transparent in the Tabler theme).

## [9.11.1] - 2026-09-13

### Fixed

- Address search: the suggestions no longer carry a "partial" tag (the precision is known only after the choice). Verified against the live Places API.

## [9.11.0] - 2026-09-13

### Changed

- Address lookup: one built-in driver, Google Maps Platform (Places Autocomplete + Place Details in one billing session, the key on the server), enabled by `GOOGLE_MAPS_KEY`. The Geoapify driver of 9.10.0 is gone; other services plug in through `Lookup\Contracts\AddressLookup` (`search()` + `resolve()`).

### Added

- `RAPYD_ADDRESS_VALIDATE` (`true` | `strict`): on save, Google Address Validation says whether the address exists as typed; `strict` refuses one it cannot find. Result kept in `verified_by`, `verified_at`, `confidence` (verified, partial, unknown).

## [9.10.0] - 2026-09-13

### Added

- Address lookup: a search box in the address form fills street, number, postcode, city, region, ISO country, state and coordinates from a geocoding service, and marks the address (`verified_by`, `verified_at`, `confidence`: verified at building level or partial). Drivers: `none` (default), `geoapify` (`RAPYD_ADDRESS_LOOKUP=geoapify`, `GEOAPIFY_KEY`, optional `RAPYD_ADDRESS_LOOKUP_COUNTRY`), or any class implementing `Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup`. Fields stay editable.
- Address form: street number field.

### Fixed

- Selectable addresses list: the radio is bound with `wire:model`, so the choice made by the server (after a delete, the default) shows in the browser; the address text is clickable too.

## [9.9.1] - 2026-09-13

### Changed

- Theme switcher of the reference theme: a light / dark toggle in the topbar (like the Tabler theme) instead of the light / dark / system dropdown. Nothing stored still means "follow the system".

## [9.9.0] - 2026-09-13

### Added

- `addresses::addresses-table-embed` has a `selectable` mode: a radio on each row, the chosen id dispatched as `selectedAddress`, the first address with a country selected by default. Used by the checkout of `zofe/shop-module`.

## [9.8.3] - 2026-09-13

### Changed

- User page: List and Edit buttons capitalised like the Companies page.

## [9.8.2] - 2026-09-13

### Fixed

- The page-size select of the tables did nothing: `wire:model` was not live (Livewire 3+ defers it). Changing the size also restarts from the first page.

## [9.8.1] - 2026-09-13

### Fixed

- `x-rpd::select-list`: the validation error never appeared in the browser. The whole component sat in the `wire:ignore` block that protects TomSelect; now only the select is ignored and the label, error and help re-render (a keyed sibling makes Livewire morph them next to the ignored block).

## [9.8.0] - 2026-09-13

### Added

- Addresses: `country_code` (ISO 3166-1) is required and chosen from a list, `state_code` (ISO 3166-2 subdivision, e.g. US states) is optional; `country` is filled from the code. Both feed the tax rules of `zofe/shop-module`.
- Companies: `vat_validated_at`, when the VAT number was last confirmed by VIES.
- `Zofe\Rapyd\Support\Countries`: ISO country codes with names, `isEu()`.

## [9.7.3] - 2026-09-13

### Fixed

- `Authorize::authorize()` for a guest: the redirect to the login page no longer goes through the `redirect()` helper, which inside a Livewire component returns Livewire's `Redirector` (no `send()` on 4.4.0: HTTP 500 on every protected page). A guest now gets a plain 302; test added.

## [9.7.2] - 2026-09-12

### Added

- Module packages (`RapydModuleServiceProvider` with `$modulePath`): the Blade files next to the Livewire classes (`Livewire/`, `Components/`) resolve as `{name}::` views, like an app module's; a `workflow.php` in the package registers its state machines. Needed by `zofe/shop-module`.

## [9.7.1] - 2026-09-12

### Fixed

- Coloured buttons and badges (`btn-danger`, `btn-success`…) had black text: `$min-contrast-ratio` lowered to 3 so Bootstrap keeps white text on the theme's saturated colours.

### Changed

- README and `docs/THEMES.md` point to `zofe/theme-tabler`, the first external theme.

## [9.7.0] - 2026-09-12

### Added

- `resources/js/rapyd-core.js`: the JavaScript of the package without the bundled stylesheet, for themes (`import '@rapyd/js/rapyd-core'`).

### Changed

- Theme overrides of the `rpd::` views live in `resources/views/rpd/` (e.g. `rpd/components/nav-link.blade.php` replaces `x-rpd::nav-link`); the previous `resources/views/components` path never matched. `docs/THEMES.md` updated. First external theme: `zofe/theme-tabler`.

## [9.6.5] - 2026-09-12

### Fixed

- `rpd:make` created an empty `resources/views/menu.blade.php` in the application even when generating into a module; the global menu is now written only when an entry goes into it.

## [9.6.4] - 2026-09-12

### Fixed

- `rpd:install` on a User model that still imports the 1.x `App\Modules\*` traits added a second `use` with the same short name (a fatal error): the legacy import is now replaced. Tests for the trait injection.

## [9.6.3] - 2026-09-12

### Fixed

- Upgrading from 1.x: roles and permissions vanished because `model_has_roles.model_type` held the User class name while 9.x registers the `user` morph alias. A migration aligns the rows (`model_has_roles`, `model_has_permissions`).

## [9.6.2] - 2026-09-12

### Fixed

- `composer.json` required `laravel/framework ^13` while CI tests Laravel 12 too: now `^12.0|^13.0`. README: Laravel 12 or 13.

## [9.6.1] - 2026-09-12

### Added

- `RapydModuleServiceProvider` works for module packages too: set `$modulePath = __DIR__` and `bootAppModule('name')` loads migrations, views, translations, routes and the `name::` Livewire components; `config.php` is merged as `config('name')`. `docs/MODULES.md` documents it (the previous text was wrong for packages), `tests/Feature/ExternalModuleTest` covers it.
- `branch-alias` `dev-main` → `9.x-dev`, so packages requiring `^9.6` resolve against a path repository.

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
