---
name: rapyd-module
description: Create or extend a Rapyd Admin module (Laravel + Livewire 4): generate the table / view / edit components with rpd:make, then add authorization, data scoping, permissions, menu entry, tests and a browser check.
---

# Rapyd Admin modules

## When to use this skill

Use it when the task is to add a feature to an application built on `zofe/rapyd-admin`: a new entity with its pages
(list, detail, form), a new page inside an existing module, a field or a filter on an existing page, a menu entry, a
permission. If the feature has a lifecycle (states that change over time), load the `rapyd-workflow` skill as well.

## What a module is

A folder in `app/Modules/{Name}/`, discovered automatically (views under the `name::` namespace, Livewire components as
`name::things-table`, routes, config and translations loaded, no service provider):

```
app/Modules/Blog/
├── Livewire/            ArticlesTable.php, ArticlesView.php, ArticlesEdit.php (+ *Embed.php for reusable blocks)
├── Views/               articles_table.blade.php, articles_view.blade.php, articles_edit.blade.php, menu.blade.php
├── Models/              Article.php (optional: the model may also live in app/Models)
├── Database/Migrations/ optional
├── Lang/en/             optional, `__('blog::articles.title')`
├── config.php           layout, menu entry, permissions
├── routes.php           full-page routes with breadcrumbs
└── workflow.php         optional, state machines (rapyd-workflow skill)
```

Bundled modules (Auth, Companies, Addresses, Workflow, Log, Search, Layout) have the same shape and are configured,
not forked; `php artisan rpd:eject Name` copies one into the app when you really have to change it.

## Procedure

### 1. Look before writing

- `php artisan rpd:context --format=text`: the modules, routes and models of this application.
- Read one existing module of this app end to end (a Livewire class, its view, routes.php, config.php, menu.blade.php)
  and keep its shape: naming, layout, how it authorizes, how it links pages.
- Check the model: does it exist? which table, which columns? Migrations are plain Laravel migrations.

### 2. Generate

```bash
php artisan rpd:make Articles Article --module=Blog     # ArticlesTable + ArticlesView + ArticlesEdit, views, routes, menu entry
php artisan rpd:make ArticlesTable Article --module=Blog   # one component: the name ends with Table, View or Edit
php artisan rpd:make:home                                  # the dashboard page of the app
```

The first argument is the **component name** (plural for a group), the second the **model**, `--module` the module
folder (created with its `config.php` and `menu.blade.php` when missing). The generated code reads the table columns to
build the list, the detail and the form.

If the model does not exist, `rpd:make:model` runs first and **asks for the fields interactively**: create the model
and the migration yourself beforehand (`php artisan make:model Article -m`, write the columns, `php artisan migrate`)
so the generation runs without prompts.

### 3. Finish what the stub leaves out

The generated components are minimal. Bring each full-page component to the shape of the reference below:

```php
namespace App\Modules\Blog\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Blog\Models\Article;
use Livewire\Component;
use Zofe\Rapyd\Traits\WithDataTable;

class ArticlesTable extends Component
{
    use WithDataTable, Authorize, Limit;

    public string $search = '';

    public function booted(): void
    {
        $this->authorize('admin|view articles');   // roles or permissions, "|" = any of them
        $this->limit();                            // the registered data scopes (companies…)
    }

    public function render()
    {
        $items = Article::ssearch($this->search)
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);

        return view('blog::articles_table', compact('items'))->layout('layout::admin');
    }
}
```

Checklist per component:
- `use Authorize` + `$this->authorize('admin|<permission>')` in `booted()` (`view things` for table / view, `edit things` for edit).
- `use Limit` + `$this->limit()` when the data belongs to companies / users.
- `->layout('layout::admin')` on `render()` (or the layout of the module's `config.php`).
- The edit component: `$rules` for every bound attribute (on an unsaved model only those survive a request), `save()`
  validates, saves, redirects to the detail page with a flash message.
- Links between pages: `route('blog.articles.view', $article)`; breadcrumbs in `routes.php` (`->crumbs(...)`).

### 4. Views: `x-rpd::` components only

```blade
{{-- list --}}
<x-rpd::card>
    <x-rpd::table title="Articles" :items="$items">
        <x-slot name="filters">
            <x-rpd::input col="col-8" debounce="350" model="search" placeholder="search…" />
            <x-rpd::select col="col-4" model="author_id" :options="$authors" placeholder="author…" addempty />
        </x-slot>
        <x-slot name="buttons">
            <x-rpd::button label="Reset" route="blog.articles" color="outline-dark" />
            <x-rpd::button label="Add" route="blog.articles.edit" color="outline-primary" />
        </x-slot>
        <table class="table">
            <thead><tr><th><x-rpd::sort model="id" label="id" /></th><th>Title</th><th></th></tr></thead>
            <tbody>
            @foreach ($items as $article)
                <tr>
                    <td><x-rpd::nav-link :label="$article->shortId ?? $article->id" route="blog.articles.view" :params="$article->id" /></td>
                    <td>{{ $article->title }}</td>
                    <td><x-rpd::icon name="edit" route="blog.articles.edit" :params="$article->id" /></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </x-rpd::table>
</x-rpd::card>

{{-- form --}}
<x-rpd::card>
    <x-rpd::edit title="Article">
        <x-slot name="buttons"><x-rpd::button label="Back" route="blog.articles" color="outline-dark" /></x-slot>
        <div class="row">
            <x-rpd::input col="col-md-8" model="article.title" label="Title" />
            <x-rpd::select-list col="col-md-4" model="article.author_id" :options="$authors" label="Author" />
            <x-rpd::rich-text col="col-md-12" model="article.body" label="Body" />
        </div>
        <x-slot name="actions"><button type="submit" class="btn btn-primary">Save</button></x-slot>
    </x-rpd::edit>
</x-rpd::card>
```

Fields: `input`, `textarea`, `select` (a plain select), `select-list` (TomSelect, `multiple`, remote `endpoint`),
`date`, `datetime`, `checkbox`, `radiogroup`, `rich-text`, `upload`, `metadata` (key / value editor). Common props:
`model`, `label`, `col`, `placeholder`, `options`, `addempty`, `help`, `debounce`, `:lazy="true"` (blur instead of live).
Modals: `<x-rpd::modal name="x" title="…" action="save" actionLabel="Save">` opened with `$this->dispatch('show-modal', ['x'])`.
Reusable blocks are `*Embed` components (`<livewire:blog::articles-comments-embed :article="$article" />`).

### 5. Permissions and menu

`app/Modules/Blog/config.php`:

```php
return [
    'layout'              => 'layout::admin',
    'menu_admin'          => 'blog::menu',
    'menu_admin_position' => 10,
    'permissions'         => ['view articles', 'edit articles'],
    'role_permissions'    => ['operator' => ['view articles', 'edit articles']],
];
```

`Views/menu.blade.php`, guarded by the same permissions:

```blade
@if(Auth::user()?->hasRoleOrPermission('admin|view articles'))
<x-rpd::nav-dropdown icon="book" label="Blog" active="/blog">
    <x-rpd::nav-link label="Articles" route="blog.articles" type="collapse-item" />
</x-rpd::nav-dropdown>
@endif
```

New permissions exist once `php artisan db:seed --class="Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder"` runs
(it creates the permissions of every module config and assigns them to the roles).

### 6. Verify

- A feature test per page with `Livewire::test('blog::articles-table')` (`assertSee`, `assertForbidden()` for a user
  without the permission, `assertRedirect` after `save`). Run `vendor/bin/phpunit` (or `composer test`).
- Open the pages in the browser as an admin **and** as a user with the module's permissions only; check the menu entry,
  the breadcrumbs, the empty state of the list, a validation error on the form. Take a screenshot.
- `php artisan optimize:clear` after touching config, routes or providers.

## Do not

- Do not hand-write Bootstrap forms or tables: use the components.
- Do not add a service provider, a `Livewire::component()` call or a `config/app.php` entry for an app module.
- Do not put a `status` column and update it by hand: that is a workflow.
- Do not scope queries by company in every component: `$this->limit()` applies the registered scopes.
- Do not use `rpd:make all …` / `rpd:make datatable …`: the first argument is the component name, not a type.
