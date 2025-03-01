# Rapyd Admin - Addresses Module

This is an utility model of [Rapyd Admin](https://github.com/zofe/rapyd-admin), a Laravel application bootstrap for your projects

It embed:

- address management for users (addresses migration & crud)
- address management for any model (via morphable relationship)


# Installation 

```bash
composer require zofe/addresses-module
php artisan migrate
```

# Usage
This module give you out of the box a way to manage addresses for users and any other model via morphable relationship
Simply place the following code in your blade file to embed the addresses table for a user:

```bladehtml

<livewire:addresses::addresses-table-embed
    addressableType="user"
    :addressableId="$user->id"
/>

```

then you can enable add/editing via another component

```bladehtml
<livewire:addresses::addresses-button-add-embed
    addressableType="user"
    :addressableId="$user->id"
/>
<livewire:addresses::addresses-modal-edit-embed />
```
