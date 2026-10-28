# Changelog

All notable changes to `rapyd-admin` will be documented in this file.

## [Unreleased]

## [9.30.0] - 2026-10-28

### Added

- **`php artisan rpd:doctor`** looks at the installation and says what is wrong, changing nothing: the config
  files published, the assets in `public/vendor/rapyd` and whether they are behind the package (the cause of a
  `composer update` that "did nothing"), migrations not run, `APP_DEBUG` in production, `admin@laravel` still on
  the password printed at setup, mail going to the log, registration open, no module yet, `boost.json` and the
  AI guideline. The same checks as the dashboard's *Getting started*, in a place an agent can run and read.
  `--fail` exits with 1 on a problem: the fresh-install job runs it as its last step.

## [9.29.0] - 2026-10-28

### Added

- **Generated lists and pages print values in the form they read best.** A boolean was a `1` or a `0`, a
  date `2026-07-23 04:04:57`, a status plain text. Now: a boolean through the new `x-rpd::bool` (an icon with
  the colour, a word — *Yes* / *No*, or the two you pass — with the meaning), a date through
  `x-rpd::date-formatted` (date, time in small, "3 days ago"; a `date` column without the time), `created_at` and
  `updated_at` the same, a `status` or `state` column as a badge (green when `active`), a long text with its
  line breaks on the page. The model gets `$casts` for its booleans, dates and decimals, so the values are what
  they say in code and in tests too. `x-rpd::date-formatted` now accepts a string or nothing, not only a Carbon.

## [9.28.0] - 2026-10-28

### Added

- **`SECURITY.md`**, and private vulnerability reporting switched on for the repository: a security problem is
  reported through *Security → Report a vulnerability* on GitHub, seen by the maintainer only, answered within a
  week and fixed in a patch release with an advisory. The file also says which versions get fixes (9.x) and what a
  stock install exposes by default (open registration, the first admin's password, the rich-text allow-list).

- **`rpd:make --company`: the module is multi-tenant from the first record.** Companies and `CompanyLimit` were
  in the core, but a generated module came with a `Limit` that let every record through and a comment on how to
  restrict it. With `--company` the model gets `company_id` (a `belongsTo` to the bundled Company, `constrained`),
  the `Limit` scopes the list to the user's company and its children — a user without a company sees nothing,
  super admins everything — the `Auth` answers 404 for another company's record, a new record takes the user's
  company in the form (the dropdown offers only the companies the user may see), the factory picks a company or
  makes one up, and the generated test proves it: two companies, an operator in each, each sees their own.
- **`--fields` knows relations: `category_id:belongsTo`.** The most common thing in an application — an article
  has a category, an order has a customer — used to be done by hand after the generator. Now `category_id:belongsTo`
  (or `category:belongsTo`, `author_id:belongsTo:User` to name the model, `:nullable`, `:search`) writes the
  foreign key in the migration (`foreignUuid` or `foreignId` after the related key, `constrained`), the
  `category()` method on the model, the related factory in the factory, an `exists` rule, and in the form a
  dropdown of the related records — `x-rpd::select` for a list that stays short, `x-rpd::select-list` (search as
  you type) for users and companies and for any relation marked `:search`. The options are a property filled in
  `mount()`, as hand-written forms do, so the query is one place to scope. The list and the detail page show the
  related record's label (`name`, `title`, `business_name`… or the first string column), not its id. The related
  model has to exist: the command says so, and how, when it does not.
- **`--fields` knows `nullable`, and the generated rules follow the column.** `phone:string:nullable` (or
  `phone:nullable`, type string by default) writes `->nullable()` in the migration; every other column stays
  required. The form's rules used to be `required` for everything: they are now built from the column as the
  database describes it — `nullable` or `required`, then `email` / `url` / `string|max:255` for strings by their
  name, `string` or `json` for text, `date` for dates, `integer` or `numeric` for numbers, `boolean` alone for
  booleans. Read from the schema, so a model written by hand gets the same treatment.
- **Required fields are bold in every form component.** `x-rpd::textarea`, `date` and `datetime` now read the
  component's rules as `x-rpd::input` always did; a form with a bold "Title" and a plain "Body", both required,
  was telling the user something false.

### Fixed

- **The generated form gave every column a one-line text input.** A `text` column had a single line for its
  paragraphs, a `datetime` had no picker, a `boolean` was typed by hand. `rpd:make:edit` now reads the column type
  and writes the matching component: `x-rpd::textarea` for text and json, `x-rpd::date` and `x-rpd::datetime`
  for dates, `x-rpd::checkbox` for booleans, a number input for integers and decimals, the text input for the
  rest. A boolean's rule is `boolean` instead of `required`: an unticked box is a value, not a missing one.
- **"Create" pages of a generated module hung under Home in the breadcrumb instead of under the list.**
  `hasTable()` of the edit generator looked for the list's view in `resources/views/livewire`, where it is only
  outside a module: in a module it never found it and fell back to `home`. It looks in the module now, as the
  view generator already did.

## [9.27.0] - 2026-10-28

### Added

- **`rpd:make` writes a feature test with the module.** `tests/Feature/Modules/<Module>/<Name>Test.php`, where
  `php artisan test` finds it: the list redirects a visitor to the login and shows the records to the admin, the
  form saves what the factory makes (every column set from a made record, dates formatted), an operator gets in
  through the module's `role_permissions` and a plain user gets 403. Written only when the table and the form are
  generated together, never over an existing file. The fresh-install job runs it in place of its own hand-written
  check.

### Fixed

- **The sidebar entry of a generated module went dark on its detail and form pages.** The entry was written with
  `active="articles/table"`, the path of the list alone; opening an article or the form left the menu with nothing
  lit. The entry now carries the prefix (`active="/articles"`), as the bundled menus do. For a module generated
  before, change that attribute in its `Views/menu.blade.php`.

## [9.26.0] - 2026-10-28

### Added

- **`rpd:make --icon=newspaper`** names the Font Awesome icon of the module's sidebar entry; without it every
  generated module got the same circle, and a sidebar of circles tells nothing. `newspaper`, `fa-newspaper` or
  a full class list are all accepted.

### Fixed

- **A long text column made the generated list unreadable.** The table printed every column as it was, so a
  `text` field with a few paragraphs (the seeder writes two) stretched each row to a screen. The list now cuts
  text and json columns to one line of 80 characters, with the first 300 in the cell's title; the detail page
  keeps the whole value.

## [9.25.0] - 2026-10-28

### Added

- **`rpd:make` writes a factory and a seeder with the model, and `--seed` fills the table.** A generated module
  opened on an empty table — "tot. 0" for whoever tried it, and no factory for whoever wanted to write a test.
  `rpd:make:model` now adds `Database/Factories/<Model>Factory.php`, with a value guessed for each column from its
  name first (`email`, `phone`, `title`, `*_name`, `status`, `code`…) and its type otherwise, and
  `Database/Seeders/<Model>Seeder.php` that creates ten records with it; in a module the model gets `HasFactory`
  and a `newFactory()` pointing there, since Laravel only looks in `Database\Factories`. `rpd:make … --seed` runs
  the seeder right away; without it the command prints the `db:seed` line. The README's first module uses `--seed`.

## [9.24.0] - 2026-10-28

### Added

- **`rpd:install` lists the package in `boost.json`, so an agent's `boost:install` brings the Rapyd guideline
  and skills along.** A fresh Laravel 13 skeleton ships an `AGENTS.md` that tells the agent to install Laravel
  Boost and run `boost:install` before anything else; run without a terminal, as agents run it, Boost asks
  nobody which third-party guidelines to add and takes the `packages` of `boost.json` as they are — and with
  no file, nothing of Rapyd reached the agent (verified on a fresh install: no guideline, no skills, with
  `--discover` too). `rpd:install` now writes `boost.json` with `"packages": ["zofe/rapyd-admin"]`, merging into
  an existing file and leaving alone one that already lists it; from there `boost:install` and `boost:update`
  add `zofe/rapyd-admin/core` to `AGENTS.md` / `CLAUDE.md` and `rapyd-module`, `rapyd-workflow` to the skills.
  Without Boost, `php artisan rpd:ai` does the same by itself, as before.

## [9.23.0] - 2026-10-28

### Added

- **`/admin` is a dashboard now, not the landing page again.** `rpd:make:home` used to generate the same hero
  for `/` and `/admin`, with a "Go to Dashboard" button that sent each page to the other. The admin home is
  now generated from its own template, into the application (yours to change): a row of tiles with the
  numbers the core already keeps — users, companies, roles, activity of the last 24 hours — each linking to
  its page and shown only to who may open it; a **Getting started** card whose items are real checks (the
  admin exists, its password was changed, a brand is set, a first module exists, Google sign-in, the AI
  guideline installed) and that disappears once all are ticked; and the last rows of the activity log. The
  route is behind `auth`: a dashboard has nothing to say to a visitor. An application that already has
  `app/Livewire/AdminHome.php` is not touched.

## [9.22.1] - 2026-10-28

The release of a fresh install done as a stranger would do it: Laravel 13.39, the README to the letter, then the
pages in a browser. The install itself took under a minute; what follows is what was wrong at the end of it.

### Fixed

- **The favicon was a 404 on every page.** `rapyd.layout.favicon` defaulted to `img/favicon.png`, a file the package
  never shipped, so every page of a new application logged a missing resource. The package now ships
  `public/img/favicon.svg` (published with the other assets) and the default points to it. An application with its
  own `config/rapyd.php` keeps its value: set `RAPYD_FAVICON` or the config key to change it.
- **`rpd:install` closed with hints that contradicted what had just happened.** Inside `rpd:make:setup` it still
  said "run migrate, publish the assets, seed `RoleSeeder` (coming soon)" — right before setup migrated and seeded.
  Setup now passes `--no-next-steps`; run on its own, the hints name the real two steps (`migrate`, then the
  `AuthSeeder`) and the `--companies` / `--uuid-users` options only when they were not used.
- **`routes/web.php` kept the skeleton's `welcome` route next to the generated home.** Two `Route::get('/')` in the
  file: Laravel took the second, the reader saw both. `rpd:make:home` now drops the skeleton's route before
  appending its own.
- **`rpd:make` said "you can run `php artisan migrate`" and then migrated by itself.** The model command just
  reports what it created.
- **Switching back to the bundled theme stopped working on Laravel 13.3x.** The view finder now stores
  namespace hints through `realpath()`, so a theme registered with `..` in its path or through a symlink (a
  path repository, say) never matched its own path in `ThemeManager::removeHint()` and stayed active after
  `?rapyd_theme=default`. Both sides are compared resolved now. The theme test caught it in CI the day the
  dependency moved.
- **The README asked for `composer require … -W`.** Not since 19 September: `league/oauth1-client` 1.12 and
  `laravel/socialite` 5.31 accept Guzzle 8, which a new Laravel 13 locks. Verified on a fresh install; the flag and
  its note are gone.
- **Forms generated by `rpd:make` rendered empty in an application installed with 9.20.2 or later.** Since 9.20.2
  `config/livewire.php` is no longer published under `laravel-assets`, and `rpd:install` did not copy it: without
  it Livewire's own defaults win (`legacy_model_binding` off), so the fields bound to a model
  (`wire:model="article.title"`) showed empty and saved nothing. `rpd:install` now copies `config/livewire.php`
  with the other configs; an existing file is kept, as for `config/rapyd.php`.
  An application installed with 9.20.2 or later gets the file with
  `php artisan vendor:publish --tag=rapyd-config`, which only adds the missing files.

## [9.22.0] - 2026-10-09

### Added

- **Application switcher in the sidebar.** An installation that is one of several sibling applications
  (single sign-on between them) can list them in `rapyd.layout.apps` — `['name' => …, 'url' => …, 'icon' => …]`
  each, or the same list as JSON in `RAPYD_APPS`. The brand keeps linking to the home as always; next to it a
  chevron opens a dropdown with the current application ticked and the others as links, opened in the same tab.
  The current one is the entry with a null url, or the one whose url has the same host as `app.url`.
  **With fewer than two entries nothing changes at all**: `rapyd_apps()` returns an empty array and the sidebar
  keeps the plain brand, so an application that does not configure it sees no difference. In the narrow sidebar
  only the chevron remains, and the menu escapes the clipping with Popper in `fixed` strategy; in the mobile
  drawer the entries are 44px tall. Colours are inherited from the theme, so it holds up on a coloured sidebar
  in light and dark mode. A theme that wants it includes `layout::includes.app_switcher`; see `docs/THEMES.md`.

### Fixed

- The arrow of the sidebar collapse button sat 4px off centre (`margin-left` on the `::after`) and was not
  centred vertically either.

## [9.21.0] - 2026-10-09

### Added

- The palette gained a `dark` block, driving the same `--rpd-*` tokens under `html.dark`, and two keys:
  `border_color` (tables, cards, dropdowns, inputs — in both modes) and `sidebar_border` (the sidebar edge and
  its dividers, derived from the sidebar text when not given). `sidebar_bg`, `topbar_bg` and `content_bg` now
  also accept a gradient.

### Changed

- **Below `md` the sidebar is a drawer** (Bootstrap `offcanvas-md`) instead of a narrow column: on a 390px
  screen it used to eat 104px and wrap the menu labels. The topbar has two distinct buttons now — the
  hamburger opens the sidebar, the kebab the navbar's own items — and picking an entry closes the drawer.
  A theme is free to use any equivalent; see "narrow screens" in `docs/THEMES.md` for the two traps
  (Bootstrap forces `background-color: transparent !important` above the breakpoint, and `#wrapper` needs
  `min-height: 100vh` once the sidebar leaves the flow).
- The light palette values are scoped to `html:not(.dark)`, so they no longer leak into dark mode. Dark mode
  keeps following the accent, lightened for contrast, when no dark `primary` is given.

### Fixed

- The navbar never collapsed on phones: `.navbar-collapse` carried `d-flex`, which is `display:flex !important`
  and beat `.collapse{display:none}`. The breadcrumb ended up over the search box and the Add button.
- The panel of the collapsed navbar was transparent in light mode — it was painted with `var(--sidebar-bg)`,
  a custom property defined only inside `.dark`, so in light mode it resolved to nothing.

## [9.20.2] - 2026-10-07

### Fixed

- `config/rapyd.php` and `config/livewire.php` were published under the `laravel-assets` tag, the one
  composer republishes with `--force` after every `composer require` or `update`: whatever the application
  had written in its `config/rapyd.php` (search models, menus, roles) was silently replaced by the package
  copy. The two files now belong to the `rapyd-config` tag with `permission.php` and `fortify.php`;
  `laravel-assets` carries the public assets only. `rpd:install` already copied the configs on its own and
  is unchanged.

## [9.20.1] - 2026-09-28

### Fixed

- `rapyd_clean_html()` was also removing what the editor writes: the `class` that carries alignment and
  indentation (`ql-align-center`, `ql-indent-1`) and the `data:` url of a pasted image. A rich-text value
  reopened after 9.20.0 lost its formatting. `class` is allowed on the blocks now, and `data:` on images —
  HTMLPurifier lets through an actual image (png, gif, jpeg, webp) and never `data:text/html`. Sanitising is
  not reformatting: a test covers it.

## [9.20.0] - 2026-09-28

Security release, from a report against a stock install. **Update, then change the password of
`admin@laravel` (or delete the account) and review any user name or rich-text value that contains markup.**

### Security

- **A company member could take over any account** (`UsersModalEditEmbed`): `editUser`, `save` and `deleteUser`
  took an id from the browser and the only check was a permission, so the `customer` role (which carries
  `edit own users`) reached the owner of its own company, accounts of other tenants and the super admin,
  changed their password, deleted them, promoted itself to `owner` and created users in other companies.
  Every entry point now resolves the id through the caller: a super admin administers anyone, the owner of a
  company its members, anybody else only himself; `role` is validated against `rapyd.companies.roles` and only
  an owner (or a super admin) hands out `owner`; `company_id` is forced to the caller's company. The icons of
  the company page follow the same rule.
- **An operator could grant himself the `admin` role** (`UsersEdit`): the roles came from the browser and were
  synced as they were. The assignable roles are now computed on the server (a non-super-admin never sees nor
  sends a role of `rapyd.auth.super_admin_roles`), a non-super-admin cannot edit a super admin (it would reset
  his password) nor add a role to himself. The dead `$unchangable` property is gone.
- **The first admin had a known password** (`AuthSeeder`): `admin@laravel` / `admin`, recreated by every
  `rpd:make`. The account is now created once with a random password printed on the console (or
  `RAPYD_ADMIN_PASSWORD`), never in production, never recreated; `rpd:make` refreshes roles and permissions
  only (`rapyd.auth.seed_admin`, `rapyd.auth.admin_email`).
- **Stored XSS through a display name** (`x-rpd::select-list`): option labels were printed raw, so a name
  chosen at registration ran in the admin session (Logs → Activity, the users and permissions forms). They are
  escaped now, like `x-rpd::select`, and a name may no longer contain `<` or `>`.
- **Stored XSS in a rich-text value** (`x-rpd::rich-text`): the value is written by the browser and was echoed
  raw when the record was reopened. It goes through `rapyd_clean_html()` (HTMLPurifier, allow-list in
  `rapyd.html_allowed`). Use that helper in your own views too, wherever such a value is printed with `{!! !!}`.

### Added

- `ezyang/htmlpurifier` as a dependency, `rapyd_clean_html()` and the `rapyd.html_allowed` config key.
- `tests/Feature/SecurityTest.php`, one case per vulnerability.

### Changed

- The user modal of the company page follows `company_role`, not a permission: an account that is not a super
  admin and not the `owner` of its company now edits only itself there, and does not create users. If your
  application relied on a role like `operator` administering the users of a company from that page, give those
  accounts `company_role = 'owner'` on the company they administer, or use the Users page (`edit users`).

## [9.19.0] - 2026-09-27

### Added

- `Zofe\Rapyd\Contracts\AiActivity` and `AiActivityProvider`: the twin of `AiTool` / `AiToolProvider`. A module
  says what the AI *did* in it, in the words of the domain ("Tickets classified", 8 845, from the categories the
  AI proposed), with the context of the ledger rows it belongs to, so ai-module can put the cost next to it. Tools
  say what the model may read; activities say what it has done — the page for the people who use the application,
  not for the ones who develop it.

## [9.18.0] - 2026-09-25

### Added

- `rapyd.skip_asset_injection`: the paths whose HTML responses do not get `rapyd.css` and `rapyd.js` injected.
  Packages that ship a complete UI of their own broke when the Bootstrap of Rapyd was added to theirs (Horizon's
  dashboard lost its grid); the default covers `horizon`, `telescope`, `pulse` and `_debugbar`, `request()->is()`
  syntax. The keys a published `config/rapyd.php` does not have yet (this one, `locale`, `locales`) take the
  package default instead of being null.

## [9.17.5] - 2026-09-21

### Fixed

- A phrase translated by hand in a `.json` catalogue is no longer listed as pending by `rpd:lang`.

## [9.17.4] - 2026-09-21

### Changed

- "form" stays "form" in every catalogue: the translated words were not understood.
- `rapyd.layout.brand_route` (`RAPYD_BRAND_ROUTE`): where the brand of the admin sidebar / navbar links, a route
  name or a URL (a public demo site sends visitors to `demo` instead of the login); `rapyd_brand_url()` for the
  themes. Default unchanged: `admin.home`, then `home`, then the root, in the language of the page.
- `docs/LOCALIZATION.md`: the application's own routes (`routes/web.php`) must be wrapped in
  `Route::prefix(app(Locales::class)->routePrefix())` to answer in every language.

## [9.17.3] - 2026-09-21

### Added

- The prompts of `resources/ai/prompts.json` (request and what it produces) are translated by the package catalogues,
  so the "Develop with AI" page of ai-module shows and copies them in the language of the page.

## [9.17.2] - 2026-09-21

### Changed

- `rpd:lang` does not add to a module's catalogue the phrases the package already translates (Back, Save, Status…):
  they are inherited from `resources/lang/{locale}.json`.
- The browser's language is matched with Symfony's `getPreferredLanguage()` (quality order, regions such as
  `pt-BR`); nothing enabled matching means the default language, no redirect.

## [9.17.1] - 2026-09-21

### Fixed

- `rpd:lang` reads a module's `routes.php` too (breadcrumb labels) and the static attributes of an `x-rpd::`
  component placed after a bound one (`:params="$article->id"` ended the tag).
- Menu entries are no longer forced to Title Case (`text-capitalize`): translated labels keep their own casing.

## [9.17.0] - 2026-09-21

### Added

- Languages, in one place (`docs/LOCALIZATION.md`): `RAPYD_LOCALES=en,it` enables them, `rapyd.locale`
  (`APP_LOCALE`) is the default one without URL prefix, `Zofe\Rapyd\Localization\Locales` knows them and
  `SetLocale` (in the `web` group) sets the language from the URL prefix, the session, the user's `users.locale`
  (new column) or the browser, redirecting a default-language page to the visitor's choice; the switcher
  (`?clang=1`) remembers it. Codes and native names instead of flags, in both navbars.
- Translations as JSON catalogues, English phrase => translation: `resources/lang/{locale}.json` of the package
  (`it` reviewed; `es`, `fr`, `de`, `pt_BR` machine-translated, listed for review in `.rpd-lang.json`),
  `Lang/{locale}.json` in every module, of the application or a package. The `x-rpd::` components translate
  `label`, `title`, `placeholder`, `actionLabel`; bundled views and generated code use `__()` on every text.
- `php artisan rpd:lang`: collects the phrases of the views into the catalogues (pending until translated,
  `--check` for CI, `--prune`, `--literals` for raw texts, a warning for phrases named like a PHP language file),
  `--translate` fills them with the AI of ai-module.

### Fixed

- Module packages (`RapydModuleServiceProvider::bootAppModule()`) registered their routes without the language
  prefix: `/it/shop` was a 404. The three copies of the prefix detection are one.

## [9.16.0] - 2026-09-20

### Changed

- `rpd:ai:status` / `AiReadiness` become `rpd:ai:develop` / `Zofe\Rapyd\Ai\AiDevelopment`: the report is about what
  the agent can do here (capabilities with the command that unlocks each), the generators, what was built in the
  project and whether it follows the conventions, the prompts to try (`resources/ai/prompts.json`, each with the
  packages it needs). Shown by ai-module 1.2 as the "Develop with AI" page.

### Added

- `rpd:make` records every run in `storage/rapyd/generated.json` (`Zofe\Rapyd\Ai\GenerationLog`: module, model,
  files written and their size) and prints the boilerplate it wrote as tokens the model did not have to generate.

## [9.15.1] - 2026-09-20

### Fixed

- `AiReadiness` (and `rpd:ai:status`) failed in an application without `app/Modules` yet.

## [9.15.0] - 2026-09-20

### Added

- `php artisan rpd:ai:status` (`--json`): is the application ready for AI-assisted development? The agent tooling
  (guideline present and current, skills present / current / customised, `CLAUDE.md`, Boost, MCP servers) with the
  command that fixes each row, the estimated context the agent loads per session, and the app modules against the
  conventions (pages without `Authorize`, workflows, permissions, `Authorizations/`, `Limits/`, tests). The report
  is `Zofe\Rapyd\Ai\AiReadiness`; ai-module 1.1 shows it as the "AI readiness" page.
- `MergesPermissions`: the `permissions` / `role_permissions` of a module's `config.php` are merged into `auth.*` for
  module packages too (`RapydModuleServiceProvider`), not only for app modules.

## [9.14.0] - 2026-09-18

### Added

- For the agents of an application: a Laravel Boost guideline (`resources/boost/guidelines/core.blade.php`) and two
  skills, `rapyd-module` and `rapyd-workflow` (`resources/boost/skills`), picked up by `boost:install` / `boost:update`;
  `php artisan rpd:ai` installs them without Boost (`.claude/skills`, `AGENTS.md` between markers, `@AGENTS.md` in `CLAUDE.md`);
  when Boost already wrote the guideline, `rpd:ai` installs only the skills.
- `scripts/sandbox.sh` (dev only): a throwaway Laravel app with rapyd-admin as path repository and Boost installed
  non-interactively, `boost.json` pre-seeded with `"packages": ["zofe/rapyd-admin"]` so the guideline and skills land.
- `docs/MODULES.md`: a Workflows section (the workflow name is the model's morph alias); `docs/AI.md`: guideline and skills.

### Changed

- Generators without prompts, for agents and scripts: `rpd:make` and `rpd:make:model` take `--fields="name:type,…"`
  (type defaults to `string`); without `--fields` the columns are asked only when a terminal is attached. `rpd:make`
  also accepts `all|table|view|edit` as component, resolved from the model's plural; `rpd:make:model` returns instead
  of calling `exit`, so the components are generated in the same run.
- Generated modules are secure by default: routes behind `auth`; `Authorize` + `Limit` in every component with
  `view <table>` / `edit <table>` permissions declared in the module's `config.php`, given to `operator` and seeded
  at generation; `Authorizations/<Model>Auth.php` and `Limits/<Model>Limit.php` as starting points (allow every
  record). New models get uuid keys (`HasUuids` + `ShortId`, `shortId` in lists) unless `--increments`.
- A module's `config.php` may declare `permissions` and `role_permissions`: merged into `auth.permissions` and
  `auth.role_permissions` by the module loader, so `AuthSeeder` creates them.
- Generated components call `->layout()` explicitly (the module's `config.php` layout, `layout::admin`): Livewire 4
  reads `livewire.component_layout`, so the published `config/livewire.php` and the `layout.config` middleware set it.

### Fixed

- Generated links (`link-view`, `link-edit`) used the label as route parameter: with `shortId` labels the detail URL
  was wrong. The parameter is now the model key, whatever the label. New models get `$fillable` from `--fields`.
- `rpd:make` with a missing model failed (`RapydMakeBaseCommand::createModel()` had a half-written statement and
  Composer caches the miss of the class); module config, menu, views and routes were written relative to the current
  directory instead of `base_path()`.
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
