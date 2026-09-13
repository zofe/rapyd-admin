# Modules

Everything in Rapyd Admin is a module: a folder with Livewire components, views, routes, config and, when needed,
models and migrations. Bundled modules live in the package, your own in `app/Modules`, third-party ones in Composer
packages. All three have the same shape, so what you learn on one applies to the others.

- [Anatomy of a module](#anatomy-of-a-module)
- [Generating a module](#generating-a-module)
- [Config: layout, menu, permissions](#config-layout-menu-permissions)
- [Bundled modules and `rpd:eject`](#bundled-modules-and-rpdeject)
- [Companies and multi-tenancy](#companies-and-multi-tenancy)
- [Writing a module as a package](#writing-a-module-as-a-package)
- [Livewire 4 notes](#livewire-4-notes)

## Anatomy of a module

```
app/Modules/Blog/
├── Livewire/            ArticlesTable.php, ArticlesView.php, ArticlesEdit.php (+ *Embed.php for reusable blocks)
├── Views/               articles_table.blade.php … and menu.blade.php (the sidebar entry)
├── Models/              optional: models that belong to the module
├── Database/Migrations/ optional
├── Lang/en/             optional: `__('blog::articles.title')`
├── config.php           layout, menu, permissions
└── routes.php           full-page Livewire routes
```

Modules in `app/Modules` are discovered automatically: views are registered under the `blog::` namespace, Livewire
components as `blog::articles-table`, routes and config are loaded, translations too. No service provider needed.

A full-page component:

```php
namespace App\Modules\Blog\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use Livewire\Component;
use Zofe\Rapyd\Traits\WithDataTable;

class ArticlesTable extends Component
{
    use WithDataTable, Authorize, Limit;

    public string $search = '';

    public function booted(): void
    {
        $this->authorize('admin|view articles');   // roles or permissions, "|" = any of
        $this->limit();                            // apply the data scopes (Companies…)
    }

    public function render()
    {
        $items = Article::ssearch($this->search)->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate($this->perPage);

        return view('blog::articles_table', compact('items'))->layout('layout::admin');
    }
}
```

Routes use the `route_lang()` helper and the breadcrumb macro:

```php
Route::get('blog/articles', ArticlesTable::class)->middleware(['web'])->name('blog.articles')
    ->crumbs(fn ($crumbs) => $crumbs->push('Articles', route_lang('blog.articles')));
```

## Generating a module

```bash
php artisan rpd:make all Article --module=Blog        # table + view + edit
php artisan rpd:make datatable Article --module=Blog  # one component only: datatable | dataview | dataedit
php artisan rpd:make:home                             # the landing / dashboard page
```

If the model does not exist, the command asks for its fields and creates model and migration first.

## Config: layout, menu, permissions

`app/Modules/Blog/config.php` is merged as `config('blog')`:

```php
return [
    'layout'              => 'layout::admin',   // full-page layout for this module's components
    'menu_admin'          => 'blog::menu',      // Blade partial included in the sidebar
    'menu_admin_position' => 10,                // order among modules
    // 'menu_frontend'    => 'blog::front_menu',
];
```

The sidebar partial uses the navigation components and checks permissions itself:

```html
@if(Auth::user()?->hasRoleOrPermission('admin|view articles'))
<x-rpd::nav-dropdown icon="book" label="Blog" active="/blog">
    <x-rpd::nav-link label="Articles" route="blog.articles" type="collapse-item" />
</x-rpd::nav-dropdown>
@endif
```

Permissions and roles are declared in `config/permission.php` (published by `rpd:install`) and seeded by
`AuthSeeder`; see [AUTH.md](AUTH.md).

## Bundled modules and `rpd:eject`

| Module | Namespace | Notes |
|---|---|---|
| Auth | `auth::` | users, roles & permissions pages, Fortify views, Google sign-in |
| Companies | `companies::` | `Company` model, `HasCompanies` user trait, `CompanyLimit` / `CompanyAuth` scopes |
| Addresses | `addresses::` | `HasAddresses` trait, `<livewire:addresses::addresses-table-embed :addressableType="'company'" :addressableId="$company->id" :editable="true" />` |
| Workflow | `workflow::` | `WorkflowTrait` on a model + a state machine in `config/workflow.php`; `<livewire:workflow::workflow-table-embed workfloableType="order" :workfloableId="$order->id" :editable="true" />` |
| Log | `log::` | `/log/app` viewer with AI analysis, `/log/activity`; `log_activity('orders', $order, ['total' => 99])` helper |
| Search | `search::` | navbar search over the models listed in `config/search.php`; models use the `SSearch` trait |
| Layout | `layout::` | the admin, frontend and auth layouts, i.e. the reference theme ([THEMES.md](THEMES.md)) |

Bundled modules are configured, not forked: colours, brand and layout through the theme, behaviour through their
config files. When you really need to change one, copy it into your app:

```bash
php artisan rpd:eject Auth      # → app/Modules/Auth, the bundled one steps aside
```

From then on that module is yours and package updates no longer reach it.

## Companies and multi-tenancy

A user belongs to at most one company (`users.company_id`, `users.company_role` = `owner` | `member`). Seeing more
than one company is a matter of hierarchy: with `RPD_TIERS=2` a tier-1 company sees itself and its children.

```dotenv
RPD_TIERS=2
RPD_TIER1_LABEL=partner
RPD_TIER2_LABEL=customer
```

```bash
php artisan rpd:install --companies     # adds HasCompanies to the User model
php artisan rpd:make:setup              # seeds a root company and a demo tenant
```

Scoping is opt-in per component: `$this->limit()` in `booted()` applies every registered `Limit`
(`CompanyLimit`: admins see everything, others their company and its children); `$this->authorize('…', $model)`
runs the registered `Authorization` classes on that model (`CompanyAuth`: own company or a child). Owner-only
actions call `CompanyOwnerAuth::check($company, $user)` explicitly.

### Addresses

Postal addresses attachable to any model (`HasAddresses` on the model, `rpd:install --addresses` adds it to User).
Embeds: `addresses::addresses-table-embed` (list; `editable`, `selectable` with a `selectedAddress` event for checkouts),
`addresses::addresses-button-add-embed`, `addresses::addresses-modal-edit-embed` (the form). The country is required
(ISO code, `Zofe\Rapyd\Support\Countries`), `state_code` optional. With a lookup service the form gets a search box that
fills the fields and marks the address as verified:

```dotenv
RAPYD_ADDRESS_LOOKUP=geoapify        # none (default) | geoapify | a class implementing Lookup\Contracts\AddressLookup
GEOAPIFY_KEY=...                     # free tier: 3,000 requests a day
RAPYD_ADDRESS_LOOKUP_COUNTRY=it      # optional bias
```

## Writing a module as a package

A package module is the same folder as an app module, plus `composer.json` and a service provider. The provider
extends `Zofe\Rapyd\Modules\RapydModuleServiceProvider`, names the module and points `$modulePath` at the folder:

```php
namespace App\Modules\Blog;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class BlogModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Blog';
    protected ?string $modulePath = __DIR__;

    public function boot(): void
    {
        if ($this->isEjected()) {          // copied to app/Modules/Blog: the app loads it
            return;
        }
        $this->bootAppModule('blog');      // migrations, views, lang, routes, Livewire "blog::" components
        // $this->registerLimit(BlogLimit::class); $this->registerAuthorization(BlogAuth::class);
    }
}
```

`config.php` is merged as `config('blog')` automatically (menu, layout, permissions). `composer.json` declares the
PSR-4 root and the provider:

```json
"autoload": { "psr-4": { "App\\Modules\\Blog\\": "./" } },
"extra": { "laravel": { "providers": ["App\\Modules\\Blog\\BlogModuleServiceProvider"] } },
"require": { "zofe/rapyd-admin": "^9.6" }
```

Keeping the `App\Modules\{Name}` namespace makes the package identical to a module generated in `app/Modules`:
users can copy it there and it keeps working. `zofe/demo-module` is the reference example, with tests.

## Livewire 4 notes

- `wire:model.lazy` is gone: every `x-rpd::` field binds `wire:model.live.debounce.150ms`; pass `:lazy="true"` for `wire:model.blur`.
- Use `#[On('event')]` instead of `$listeners`, and `$this->dispatch()` instead of `emit()`.
- On a model that is not saved yet, only the attributes listed in `$rules` survive between requests.
- Module components in Blade use `.` as directory separator: `<livewire:blog::subdir.component />`.
- Livewire ships Alpine.js: do not include a separate Alpine bundle.
