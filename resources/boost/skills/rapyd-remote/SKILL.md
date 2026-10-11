---
name: rapyd-remote
description: "Show entities owned by another Laravel app (customers, services, devices…) inside a Rapyd Admin app without copying them: expose a resource on the owner app, read it on the other with the Remote module of Rapyd Admin (experimental, enable with RAPYD_REMOTE_ENABLED=true)."
---

# Rapyd Admin: Remote module

## When to use this skill

The task needs data that another app owns: "show the CRM customer on the device page", "pick a customer from the CRM",
"choose the services of this device". Use it instead of copying tables or calling another app's database.
Reading only: never write to a remote entity, link to the owner app instead.

## Owner app: expose

In `AppServiceProvider::boot()` (`use Zofe\Rapyd\Modules\Remote\Expose\RemoteExpose;`):

```php
RemoteExpose::resource('customers', Customer::class)
    ->searchable(['name', 'vat_number'])   // columns matched by ?q=
    ->fields(['name', 'vat_number'])       // attributes sent to the other app
    ->label(fn ($c) => $c->name)
    ->scope(fn ($query, $user) => $query->where('tenant_id', $user?->tenant_id));
```

`scope` must apply the same visibility the user has in this app. Add the caller token to `config('rapyd.remote.callers')`.

## Reading app: use

`config('rapyd.remote.sources')` holds the owner's url and token. A reference is `"source.resource"`.

- Single id on a model: `use \App\Modules\Remote\Traits\HasRemote;` and a method `customer(): ?RemoteRecord { return $this->remote('crm.customers', 'customer_id'); }`. Read `->label` and `->payload['field']`.
- Several ids: a json column cast to `array` and `remoteMany('crm.services', 'service_ids')`.
- Form: `<x-remote::select resource="crm.customers" wire:model="customer_id" :value="$customer_id" />`; short lists: `<x-remote::checkboxes resource="crm.services" wire:model="service_ids" />`.
- Display: `<x-remote::label resource="crm.customers" :id="$device->customer_id" />`.
- Tables: call `Remote::many('crm.customers', $items->pluck('customer_id'))` before rendering, one HTTP call per page.

## Do not

- Do not store remote data in your own columns other than the id: the cache (`remote_records`) does that.
- Do not create relations with `belongsTo` to a remote table: it lives in another database.
