# Themes

A theme is the look of the admin area: the HTML shell, sidebar, topbar, login page, colours. It is a folder
(usually a Composer package) with Blade layouts and compiled assets that replaces the bundled look without
touching the modules or the `x-rpd::` data components. Start from a Bootstrap 5 HTML template, map its regions
onto the contract below, run `php artisan rpd:theme:check`, done.

This document is the contract. It is written for developers and for AI agents alike: follow it literally.

## How a theme is resolved

1. The theme's ServiceProvider extends `Zofe\Rapyd\Themes\RapydThemeServiceProvider` and sets `$name` and `$path`.
2. When `config('rapyd.theme')` (env `RAPYD_THEME`) equals `$name`, the theme's `resources/views` is looked up
   **before** the bundled views on the `layout` namespace, and `resources/views/rpd` (if present) before the
   package's `rpd` views: `resources/views/rpd/components/nav-link.blade.php` replaces `x-rpd::nav-link`.
   Modules keep calling `->layout('layout::admin')`: nothing changes for them.
3. `@rapydStyles` / `@rapydScripts` emit `public/vendor/themes/{name}/rapyd.css` and `rapyd.js`, published from
   the theme's `public/` folder by `vendor:publish` (tag `laravel-assets` or `rapyd-theme-{name}`).
   A layout that does not call them gets them injected before `</head>` and `</body>`, so a page of the application
   always has them. The pages listed in `config('rapyd.skip_asset_injection')` never do: packages with a complete UI
   of their own (Horizon, Telescope, Pulse, Nova…) break when the Bootstrap of Rapyd is added to theirs. The default
   covers `horizon`, `telescope`, `pulse` and `_debugbar`; add the paths of any other such package, `request()->is()`
   syntax (`nova`, `nova/*`).
4. One theme at a time. A registered but inactive theme changes nothing.
   With `RAPYD_THEME_SWITCH=true` (config `rapyd.theme_switch`) each visitor can pick one: a palette icon in the navbar
   lists the bundled look (`default`) and every registered theme, `?rapyd_theme=<name>` stores the choice in the session
   and the following requests render with it (`RAPYD_THEME_PICKER=false` keeps the links working without the icon).
   Publish the assets of every theme you offer. Meant for demos and for
   trying themes side by side; a theme's own navbar should `@include('layout::includes.theme_picker')` (or its own
   markup built on `Zofe\Rapyd\Themes\ThemeManager`: `names()`, `active()`, `ThemeManager::QUERY`).
5. An application can still override single views in `resources/views/vendor/layout/`: that layer wins over the theme.

## Folder layout of a theme

```
theme-acme/
├── composer.json                 zofe/theme-acme, autoload Zofe\ThemeAcme\, extra.laravel.providers
├── src/ThemeServiceProvider.php  extends RapydThemeServiceProvider ($name = 'acme', $path = __DIR__.'/..')
├── theme.json                    name, version, screenshot, "rapyd-admin": "^9.6"
├── resources/views/
│   ├── app.blade.php             HTML shell
│   ├── admin.blade.php           admin area (extends layout::app)
│   ├── frontend.blade.php        public area with navbar (extends layout::app)
│   ├── auth.blade.php            login / register / password / 2FA shell
│   ├── includes/…                sidebar, navbar, user dropdown… (free)
│   └── rpd/components/           OPTIONAL overrides of nav-dropdown, nav-link, nav-item, breadcrumbs, notifies
├── resources/sass/theme.scss     variables → bootstrap → rapyd-base → your layout
├── resources/js/theme.js         imports @rapyd/js/rapyd-core (Bootstrap, TomSelect, modals, theme switcher) + theme.scss
├── vite.config.js                copy of the package one, outputs rapyd.css / rapyd.js into public/
└── public/                       compiled: rapyd.css, rapyd.js, fonts/, img/ (committed)
```

The bundled look lives in `src/Modules/Layout/Views` of `zofe/rapyd-admin` and follows the same contract:
copy it as the starting point of a new theme. [zofe/theme-tabler](https://github.com/zofe/theme-tabler) is a complete
example built from a third-party template: read it next to this document.

## The layout contract

Names are fixed. A theme may add anything else, but everything listed here must be present, because modules,
components and applications rely on it. `rpd:theme:check` verifies the list.

### `app.blade.php` — HTML shell

| Region | What it is |
|---|---|
| `@yield('title', …)` | page title, default `config('rapyd.layout.brand')` then `app.name` |
| `@rapydStyles` | theme stylesheet (+ anything pushed to the `rapyd_styles` stack) |
| `config('rapyd.layout.custom_css')` | optional extra stylesheet, loaded after the theme |
| `@livewireStyles` | |
| `@stack('head_scripts')` | |
| `@section('main') … @show` (or `@yield('main')`) and `{{ $slot ?? '' }}` | the page body, filled by admin/frontend |
| `@aiWidget` | AI chat widget, no-op when the ai-module is absent |
| `@livewireScripts`, `@rapydScripts`, `@stack('footer_scripts')` | in this order, before `</body>` |

### `admin.blade.php` — admin area

| Region | What it is |
|---|---|
| brand | `config('rapyd.layout.logo_sidebar')` as image, else `config('rapyd.layout.brand')` / `app.name`; links to `admin.home` or `home` when they exist |
| menu | `@foreach(config('rapyd.menus.admin', []) as $menu) @include($menu) @endforeach`, then `@includeIf('menu')` and `@yield('role_menu')` |
| `@stack('sidebar_footer')` | hook at the bottom of the sidebar |
| application switcher | `rapyd_apps()` returns the sibling applications (`rapyd.layout.apps`, or `RAPYD_APPS` as JSON) with the current one flagged; with fewer than two it returns an empty array and the sidebar keeps the plain brand. The bundled layout shows them through `layout::includes.app_switcher`: the brand still links to the home, a 44px chevron opens a Bootstrap dropdown, the current entry is a non-clickable `aria-current="true"` item and the others are links in the same tab. A theme is free to leave it out, but if it wants it, including that partial is enough |
| narrow screens | below `md` the sidebar has to get out of the way, not shrink. The bundled layout does it with Bootstrap's `offcanvas-md offcanvas-start` on the sidebar plus a `data-bs-toggle="offcanvas"` button in the topbar; a theme may use any equivalent drawer. Two things come with it: above the breakpoint Bootstrap forces `background-color: transparent !important` on `.offcanvas-md`, so the sidebar background must be restated by a more specific rule (and the fallback has to work in dark mode too — see `--rpd-sidebar-bg-fallback`), and `#wrapper` needs `min-height: 100vh`, since a drawer no longer holds the page up |
| search | `@livewire('search::search-navbar')` when `rapyd.search.enabled` and `Route::has('search.items')` |
| locale switcher | `@include('layout::includes.locale_switcher')`, shown when `config('rapyd.locales')` has more than one language ([LOCALIZATION.md](LOCALIZATION.md)) |
| `@stack('navbar_right')` | hook in the right part of the topbar |
| user dropdown | name, company, Profile (`Route::has('profile')`), impersonation leave, logout form (`route('logout')`), `@yield('user_info_dropdown')` |
| theme switcher | a light / dark toggle (icon shows what the click gives; the choice lives in localStorage, nothing stored = system) |
| `<x-rpd::breadcrumbs />` | |
| `@stack('page_header')` | hook above the page content |
| messages | `@include('layout::includes.messages')` or equivalent (session `success`, `status`, `message`) |
| `@yield('main-content')` and `{{ $slot ?? '' }}` | the page |
| `@yield('doc')` | documentation slot under the page |
| `@stack('footer')` | hook in the footer |

### `frontend.blade.php` — public area

`config('rapyd.menus.frontend')` loop, `@section('left_navbar')`, `@stack('right_navbar')`, login/register links
guarded by `Route::has`, user dropdown + theme switcher when logged in, `@yield('main-content')` and `$slot`.

### `auth.blade.php` — authentication pages

`@yield('title', …)`, `@rapydStyles`, `@livewireStyles`, `@stack('head_scripts')`, `@yield('styles')`,
`@yield('main-content')` and `$slot`, `@livewireScripts`, `@rapydScripts`, `@stack('footer_scripts')`, `@yield('scripts')`.
The pages use `.auth-card` / `.auth-card-narrow` and `config('rapyd.layout.logo_login')`.

### Rules

- Every route is optional: always `Route::has()` before `route()`.
- Read branding from `config('rapyd.layout.*')`, never from `app.name` directly (use it only as fallback).
- Do not override the data components (`card`, `table`, `view`, `edit`, `input`, `select`…): they are the package's.
  Navigation components (`nav-dropdown`, `nav-link`, `nav-item`, `breadcrumbs`, `notifies`) may be overridden.
- Keep Bootstrap 5 class names: the components are plain Bootstrap markup.
- The dark mode toggles the `dark` class on `<html>` via `ThemeSwitcher` in `rapyd.js`: style `html.dark` in SCSS.

## Design tokens and runtime palette

The components do not hard-code the accent colour: `resources/sass/_tokens.scss` maps `.btn-primary`, outline buttons,
focus rings, checks, pagination, active items and badges onto Bootstrap's CSS variables (`--bs-primary`,
`--bs-primary-rgb`, `--bs-link-color`…), deriving hover and tint shades with `color-mix()`. The layout reads a few
tokens of its own, in light and in dark mode, each falling back to the theme's compiled value:

| Token | Used by |
|---|---|
| `--rpd-sidebar-bg` | `.sidebar`, `.bg-sidebar` |
| `--rpd-sidebar-inner-bg`, `--rpd-sidebar-active-bg` | open sub-menus and the active item (derived from `sidebar_bg` by the palette) |
| `--rpd-sidebar-border` | the sidebar edge and its dividers (derived from `sidebar_bg` when not given) |
| `--rpd-topbar-bg` | `.navbar-admin` |
| `--rpd-content-bg` | `#content-wrapper` |
| `--rpd-border-color` | tables, cards, dropdowns and inputs, in light and dark mode (`border_color`) |

So an application changes the look **without recompiling**, from `.env`:

```dotenv
RAPYD_PRIMARY="#6a1c9a"
RAPYD_SIDEBAR_BG="#1e293b"
RAPYD_SIDEBAR_TEXT="#f8fafc"
RAPYD_SIDEBAR_BORDER="rgba(0, 0, 0, .35)"
RAPYD_TOPBAR_BG="#ffffff"
RAPYD_CONTENT_BG="#f1f5f9"
RAPYD_BORDER_COLOR="#b7c0d4"
```

Quote the values: in a `.env` file an unquoted `#` starts a comment.

`config('rapyd.layout.palette')` is turned by `Zofe\Rapyd\Themes\Palette` into a `<style id="rapyd-palette">` that
`@rapydStyles` emits right after the theme stylesheet. The keys above are scoped to `html:not(.dark)`, so they never
leak into dark mode; `primary` also reaches dark mode, lightened for contrast when no dark accent is given.

### Dark mode

A `dark` sub-array drives the very same tokens under `html.dark`; anything it leaves out keeps the theme's own dark value:

```php
'palette' => [
    'sidebar_bg' => '#4e73df',
    'dark' => ['sidebar_bg' => '#16244a'],
],
```

The three background keys (`sidebar_bg`, `topbar_bg`, `content_bg`) also accept a gradient — `linear-gradient(…)`,
`radial-gradient(…)`, `conic-gradient(…)` — which is why the layout sets them with the `background` shorthand. With a
gradient sidebar the derived `--rpd-sidebar-inner-bg` / `--rpd-sidebar-active-bg` become translucent black instead of a
`color-mix()` of the base colour.

The palette retouches the theme in place, per application. A different look to install and pick from the theme
switcher is a **theme** of its own: `zofe/theme-desk` is the smallest possible one — it keeps the bundled layout and
only changes the colours, so its whole stylesheet is `@import "@rapyd/sass/rapyd"` plus the token values.

A theme must keep this working: import `@rapyd/rapyd-base` (which includes the tokens) and use the three `--rpd-*`
tokens for its sidebar, topbar and content backgrounds.

## Assets

`resources/sass/theme.scss` of a theme:

```scss
@import "variables";                              // your Bootstrap overrides
@import "bootstrap/scss/bootstrap";
@import "@rapyd/rapyd-base";                      // rapyd components + tom-select (from zofe/rapyd-admin)
@import "layout/sidebar";                         // your layout
```

`vite.config.js`: copy the one of `zofe/rapyd-admin`, set the entry to your `resources/js/theme.js` (which imports
`theme.scss`) and add the alias `'@rapyd': 'vendor/zofe/rapyd-admin/resources/sass'`. Build with `npm run build`,
commit `public/`. The app picks the files up with `php artisan vendor:publish --tag=laravel-assets --force`.

## Verify

```bash
php artisan rpd:theme:check                      # active theme (or the bundled layout)
php artisan rpd:theme:check path/to/resources/views
```

Then open the login page, an admin table, an edit form and a modal, in light and dark mode. For an AI agent: take a
screenshot of each and compare with the bundled look.
