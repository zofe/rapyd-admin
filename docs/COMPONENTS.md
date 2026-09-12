# Components

The `x-rpd::` Blade components are the building blocks of every page: a datatable, a detail page, a form, the
fields inside it and the navigation. They are anonymous Blade components in `resources/views/components` of the
package: publish one to `resources/views/vendor/rpd` to change its markup.

- [Table](#table) · [View](#view) · [Edit](#edit)
- [Form fields](#form-fields)
- [Navigation](#navigation-components)
- [Livewire 4 notes](#livewire-4-notes)

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
        <x-rpd::button :route="['articles.edit', $model->getKey()]" color="outline-primary" label="edit" />
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

## Livewire 4 notes

- `wire:model.lazy` has been removed in LW4. All `x-rpd::` field components default to `wire:model.live.debounce.150ms`. Pass `:lazy="true"` to use `wire:model.blur`.
- Module components referenced in Blade views use `.` as directory separator in the namespace: `livewire:mymodule::subdir.component-name`.
- LW4 ships Alpine.js 3.14 internally — do not include a separate Alpine bundle.
