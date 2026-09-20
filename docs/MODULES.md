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
php artisan rpd:make Articles Article --module=Blog --fields="title,body:text,published_at:datetime"
# ArticlesTable + ArticlesView + ArticlesEdit, views, routes, menu entry; when the model does not exist,
# the model and its migration (columns from --fields, type defaults to string) and the migration runs
php artisan rpd:make ArticlesTable Article --module=Blog   # one component: a name ending with Table, View or Edit
php artisan rpd:make all Article --module=Blog             # all|table|view|edit: the name comes from the model
php artisan rpd:make:home                                  # the landing / dashboard page
```

The first argument is the **component name** (a plural, a full name ending with `Table`, `View` or `Edit`, or one of
`all` `table` `view` `edit`), the second the model. A new model needs `--fields` (`name:type` pairs, types: `string`
`text` `integer` `boolean` `date` `datetime` `float` `decimal` `json` `timestamp`): without it the command asks for the
columns only when a terminal is attached, otherwise the table gets just `id` and timestamps. An existing model is never
touched. Nothing is interactive, so an agent or a script can run the whole generation in one command.

What is generated is secure by default: routes behind `auth`, `Authorize` / `Limit` in every component with the
`view <table>` / `edit <table>` permissions declared in `config.php` (given to `operator` and seeded at once), an
`Authorizations/<Model>Auth.php` and a `Limits/<Model>Limit.php` that allow every record and are the place to restrict
by company or user, uuid keys with `HasUuids` + `ShortId` (`--increments` for an integer id), `render()` on the layout
of the module's `config.php`. What is left to you: the fields of the views, the `Limit` / `Authorization` rules, a test.

## Config: layout, menu, permissions

`app/Modules/Blog/config.php` is merged as `config('blog')`:

```php
return [
    'layout'              => 'layout::admin',   // full-page layout for this module's components
    'menu_admin'          => 'blog::menu',      // Blade partial included in the sidebar
    'menu_admin_position' => 10,                // order among modules
    // 'menu_frontend'    => 'blog::front_menu',
    'permissions'         => ['view articles', 'edit articles'],           // merged into auth.permissions
    'role_permissions'    => ['operator' => ['view articles', 'edit articles']], // and auth.role_permissions
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

The `permissions` and `role_permissions` of every module config join those of the Auth module and are created by
`AuthSeeder` (`php artisan db:seed --class="Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder"`, idempotent);
see [AUTH.md](AUTH.md).

## Bundled modules and `rpd:eject`

| Module | Namespace | Notes |
|---|---|---|
| Auth | `auth::` | users, roles & permissions pages, Fortify views, Google sign-in |
| Companies | `companies::` | `Company` model, `HasCompanies` user trait, `CompanyLimit` / `CompanyAuth` scopes |
| Addresses | `addresses::` | `HasAddresses` trait, `<livewire:addresses::addresses-table-embed :addressableType="'company'" :addressableId="$company->id" :editable="true" />` |
| Workflow | `workflow::` | `WorkflowTrait` on a model + a state machine in the module's `workflow.php` (see [Workflows](#workflows)); `<livewire:workflow::workflow-table-embed workfloableType="order" :workfloableId="$order->id" :editable="true" />` |
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
(ISO code, `Zofe\Rapyd\Support\Countries`), `state_code` optional. With a Google Maps Platform key the form gets a
search box: type a few words, pick a suggestion, the fields and the coordinates are filled and the address is marked as
verified (`verified_by`, `verified_at`, `confidence`). The key stays on the server.

```dotenv
GOOGLE_MAPS_KEY=...                 # Places API (New) enabled; the search box appears
RAPYD_ADDRESS_VALIDATE=strict       # optional, Address Validation API enabled: refuse an address Google cannot find
RAPYD_ADDRESS_LOOKUP_COUNTRY=it     # optional bias
```

Costs (September 2026): suggestions are grouped in a free billing session, one Place Details per chosen address
(10,000 free a month), one validation per saved address (5,000 free a month). Another service: a class implementing
`Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup` in `RAPYD_ADDRESS_LOOKUP`.

**Getting the key.** Google needs a Cloud project with a billing account (a card on file, even if you stay within the
free usage); there is no key without a Google account. In the console:

1. https://console.cloud.google.com, sign in, create a project.
2. https://console.cloud.google.com/billing: add a billing account and link it to the project.
3. APIs & Services → Library: enable *Places API (New)* and, for `RAPYD_ADDRESS_VALIDATE`, *Address Validation API*.
4. APIs & Services → Credentials → Create credentials → API key. Restrict it to those two APIs and, in production,
   to your server's IP.
5. Put the key in `GOOGLE_MAPS_KEY`.

The same, after the billing step, from the terminal with the [gcloud CLI](https://cloud.google.com/sdk/docs/install)
(`brew install --cask google-cloud-sdk` on macOS):

```bash
gcloud auth login
gcloud projects create my-shop-maps --name="My shop maps"
gcloud config set project my-shop-maps
gcloud billing accounts list                                   # copy the ACCOUNT_ID
gcloud billing projects link my-shop-maps --billing-account=ACCOUNT_ID
gcloud services enable places.googleapis.com addressvalidation.googleapis.com
gcloud services api-keys create --display-name="rapyd-admin" \
    --api-target=service=places.googleapis.com \
    --api-target=service=addressvalidation.googleapis.com \
    --allowed-ips=1.2.3.4                                      # your server's IP; omit while developing
```

The last command prints `keyString`: that is `GOOGLE_MAPS_KEY`. The key is restricted to the two APIs (and to your
server's IP when you pass it), so it is safe in the `.env` of the server.

## Workflows

A model with a lifecycle (an order, a ticket, a request) gets the `Zofe\Rapyd\Modules\Workflow\Traits\WorkflowTrait`
and a state machine declared in the module's `workflow.php` (`app/Modules/{Name}/workflow.php`, or the root of a
package module): places, transitions and their metadata (`label`, `class` for the button colour, `final` on terminal
places, `action` for a transition that opens a modal). The definitions of every module are merged into
`config('workflow')`, read by `zerodahero/laravel-workflow`.

**The name of the workflow must be the morph alias of its model** (`Relation::morphMap(['order' => Order::class])`):
the embed takes one value, `workfloableType`, and uses it both to find the model and to pick the workflow.

```php
// app/Modules/Support/workflow.php
return [
    'ticket' => [
        'type' => 'state_machine',
        'marking_store' => ['type' => 'single_state', 'property' => 'status'],
        'initial_marking' => 'open',
        'supports' => [Ticket::class],
        'places' => ['open' => ['metadata' => ['label' => 'open']], 'closed' => ['metadata' => ['label' => 'closed', 'final' => true]]],
        'transitions' => ['close' => ['from' => ['open'], 'to' => 'closed', 'metadata' => ['label' => 'close ticket']]],
    ],
];
```

Rules and effects are listeners of the workflow events (`workflow.ticket.guard.close` → `$event->setBlocked(true,
'why')`, `workflow.ticket.completed.close` → side effects), grouped in a subscriber registered with
`Event::subscribe()`. The page shows the transitions and the history with the embed; a transition applied by code is
`$ticket->workflow_apply('close', 'ticket'); $ticket->save();`. The embed asks for the `view workflow` / `edit workflow`
permissions (admins have everything). The `rapyd-workflow` agent skill ([AI.md](AI.md)) is the long version.

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
