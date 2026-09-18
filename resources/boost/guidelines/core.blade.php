## Rapyd Admin

This application is built on Rapyd Admin (`zofe/rapyd-admin`): a modular admin panel on Laravel + Livewire 4 + Bootstrap 5.3.
Rapyd Admin is **not** a framework inside the framework: it generates plain Laravel and Livewire code that lives in the
application, with a few conventions and a set of Blade components. Write code that a Laravel developer reads without
learning anything new: no DSL, no magic, no Filament / Nova patterns.

### The three rules

1. **Everything is a module.** Features live in `app/Modules/{Name}/` (Livewire/, Views/, Models/, routes.php,
   config.php, workflow.php). Modules are discovered automatically: no service provider, no `config/app.php` entry.
   Before adding a feature, look at how the existing modules of this app are shaped and copy that shape.
2. **Pages are made of `x-rpd::` components.** A list is `<x-rpd::table>`, a detail page `<x-rpd::view>`, a form
   `<x-rpd::edit>` with `<x-rpd::input|select|select-list|date|datetime|checkbox|radiogroup|rich-text|upload>` fields,
   navigation with `<x-rpd::nav-dropdown|nav-link|nav-item|button|icon>`. They are plain Bootstrap markup: never
   hand-write a form or a table when a component exists (see `docs/COMPONENTS.md` of the package).
3. **State is a workflow, not a string.** A model with a lifecycle (an order, a ticket, a request) gets the
   `WorkflowTrait` and a state machine in the module's `workflow.php`; the pages show
   `<livewire:workflow::workflow-table-embed>` for the transitions and the history. Never write `$model->status = 'x'`
   by hand: define a transition, apply it, let the listeners do the side effects.

### Conventions to keep

- Full-page Livewire components: `use WithDataTable, Authorize, Limit;`, `$this->authorize('admin|view things')`
  and `$this->limit()` in `booted()`, `render()` returns `view('module::view')->layout('layout::admin')`.
- Routes: `Route::get('things', ThingsTable::class)->middleware(['web'])->name('things.table')->crumbs(fn ($crumbs) => …)`.
- Permissions are strings (`view things`, `edit things`) declared in the module's `config.php` and checked with
  `authorize('admin|edit things')`; the sidebar entry (`Views/menu.blade.php`) checks them too.
- Livewire 4: `#[On('event')]` not `$listeners`, `$this->dispatch()` not `emit()`; fields bind
  `wire:model.live.debounce.150ms` by default; on an unsaved model only the attributes in `$rules` survive a request.
- Generate, then finish: `php artisan rpd:make Things Thing --module=Name` writes the table / view / edit components,
  views, routes and the menu entry; then add authorization, scoping and the layout the stub leaves out.
- Verify like a developer: `vendor/bin/phpunit` (or `composer test`), then open the page you touched. Take a screenshot.

### Skills

Load the `rapyd-module` skill to create or extend a module, `rapyd-workflow` to design or implement a state machine.
`php artisan rpd:context --format=text` prints the modules, routes and models of this application.
