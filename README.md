# Rapyd Admin

<a href="https://github.com/zofe/rapyd-admin/actions/workflows/run-tests.yml"><img src="https://github.com/zofe/rapyd-admin/actions/workflows/run-tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/zofe/rapyd-admin"><img src="https://img.shields.io/packagist/dt/zofe/rapyd-admin" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/zofe/rapyd-admin"><img src="https://img.shields.io/packagist/v/zofe/rapyd-admin" alt="Latest Stable Version"></a>

[![rapyd.dev](screencast.gif)](https://rapyd.dev)

**Rapyd Admin** is an open-source admin panel for Laravel, powered by Livewire 4 and Bootstrap 5.

**[Live demo →](https://rapyd.dev/demo)**

---

## Requirements

- PHP 8.2+
- Laravel 11 / 12 / 13
- Livewire 4

---

## Installation

```bash
composer create-project --prefer-dist laravel/laravel myapp
cd myapp
composer require zofe/rapyd-admin
```

Run the setup command to configure the database, publish configs, and seed the default admin user:

```bash
php artisan rpd:make:setup
php artisan serve
```

Login with the default admin account:

```
email:    admin@laravel
password: admin
```

---

## What's included

Rapyd Admin ships three bundled modules — no extra packages needed:

- **Layout** — navbar/sidebar based on SBAdmin 3, updated to Bootstrap 5.3, SCSS customizable, anonymous Blade components.
- **Auth** — authentication via Laravel Fortify, Socialite, 2FA, and role/permission management via `spatie/laravel-permission`.
- **Companies** — multi-tenant company hierarchy (1–3 tiers), with optional UUID primary keys.

---

## Generators

### Livewire component

```bash
php artisan rpd:make UserTable User
```

Generates `app/Livewire/UserTable.php` and its blade view.

### Module (Table + View + Edit)

```bash
php artisan rpd:make Articles Article --module=Blog
```

Creates `app/Modules/Blog/` with:

```
app/Modules/Blog/
├── Livewire/
│   ├── ArticlesEdit.php
│   ├── ArticlesTable.php
│   └── ArticlesView.php
├── Views/
│   ├── articles_edit.blade.php
│   ├── articles_table.blade.php
│   └── articles_view.blade.php
└── routes.php
```

---

## Blade Components

### Table

Datatable with filters, sorting, and pagination:

```html
<x-rpd::table title="Articles" :items="$items">

    <x-slot name="filters">
        <x-rpd::input col="col-8" debounce="350" model="search" placeholder="search..." />
        <x-rpd::select col="col-4" model="author_id" :options="$authors" placeholder="author..." addempty />
    </x-slot>

    <table class="table">
        <thead><tr>
            <th><x-rpd::sort model="id" label="id" /></th>
            <th>title</th>
        </tr></thead>
        <tbody>
        @foreach ($items as $article)
        <tr>
            <td><a href="{{ route('articles.view', $article->id) }}">{{ $article->id }}</a></td>
            <td>{{ $article->title }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

</x-rpd::table>
```

### View

Detail page with buttons and actions:

```html
<x-rpd::view title="Article Detail">
    <x-slot name="buttons">
        <x-rpd::button route="articles" color="outline-primary" label="list" />
        <x-rpd::button :href="route('articles.edit', $model->getKey())" color="outline-primary" label="edit" />
    </x-slot>
    <div>Title: {{ $article->title }}</div>
</x-rpd::view>
```

### Edit

Form bound to a Livewire model:

```html
<x-rpd::edit title="Article Edit">
    <x-rpd::input model="article.title" label="Title" />
    <x-rpd::rich-text model="article.body" label="Body" />
</x-rpd::edit>
```

---

## Form Fields

All field components use `wire:model.live.debounce.150ms` by default. Pass `:lazy="true"` to switch to `wire:model.blur`.

```html
<x-rpd::input model="search" debounce="350" placeholder="search..." />

<x-rpd::select model="author_id" :options="$authors" />

<!-- TomSelect dropdown, supports remote endpoint -->
<x-rpd::select-list model="roles" multiple :options="$available_roles" label="Roles" />
<x-rpd::select-list model="roles" multiple endpoint="/ajax/roles" label="Roles" />

<x-rpd::date model="date" format="dd/MM/yyyy" value-format="yyyy-MM-dd" label="Date" />
<x-rpd::datetime model="date_time" format="dd/MM/yyyy HH:mm" value-format="yyyy-MM-dd HH:mm:ss" label="DateTime" />

<x-rpd::textarea model="body" label="Body" rows="5" />

<!-- Quill WYSIWYG -->
<x-rpd::rich-text model="body" label="Body" />

<x-rpd::upload model="file" label="Upload" />

<x-rpd::checkbox model="active" label="Active" />

<x-rpd::radiogroup model="status" :options="['active','inactive']" label="Status" />
```

**Common props:** `label`, `placeholder`, `model`, `options`, `debounce`, `prepend`, `append`, `help`, `icon`, `size`, `multiple`, `endpoint`, `format`, `value-format`, `rows`.

---

## Navigation Components

```html
<!-- Sort link inside a datatable -->
<x-rpd::sort model="id" label="id" />

<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <x-rpd::nav-link label="Home" route="home" />
    <x-rpd::nav-link label="Articles" route="articles" />
</ul>

<!-- Sidebar with grouped items -->
<x-rpd::sidebar title="Rapyd.dev" class="p-3 text-white border-end">
    <x-rpd::nav-item label="Demo" route="demo" active="/rapyd-demo" />
</x-rpd::sidebar>

<!-- Collapsible dropdown in sidebar -->
<x-rpd::nav-dropdown icon="fas fa-fw fa-book" label="KnowledgeBase" active="/kb">
    <x-rpd::nav-link label="Edit Articles" route="kb.admin.articles.table" type="collapse-item" />
</x-rpd::nav-dropdown>
```

---

## Companies / Multi-tenancy

Enable Companies in your `.env`:

```env
RPD_TIERS=2
RPD_TIER1_LABEL=partner
RPD_TIER2_LABEL=customer
```

Run `php artisan rpd:make:setup` — it will seed a root company and a demo tenant automatically.

To enable company scoping on your User model, run:

```bash
php artisan rpd:install --companies
```

---

## Livewire 4 notes

- `wire:model.lazy` has been removed in LW4. All `x-rpd::` field components default to `wire:model.live.debounce.150ms`. Pass `:lazy="true"` to use `wire:model.blur`.
- Module components referenced in Blade views use `.` as directory separator in the namespace: `livewire:mymodule::subdir.component-name`.
- LW4 ships Alpine.js 3.14 internally — do not include a separate Alpine bundle.

---

## Credits

- [Felice Ostuni](https://github.com/zofe)
- [All Contributors](../../contributors)

Inspired by:
- [rapyd-laravel](https://github.com/zofe/rapyd-laravel) — the original library (150k+ downloads)
- [livewire](https://livewire.laravel.com/)
- [laravel-bootstrap-components](https://github.com/bastinald/laravel-bootstrap-components)

## License

MIT — [http://opensource.org/licenses/MIT](http://opensource.org/licenses/MIT)

