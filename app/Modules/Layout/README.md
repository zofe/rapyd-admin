# Rapyd Admin - Layout Module

This is the Layout module of [Rapyd Admin](https://github.com/zofe/rapyd-admin), a Laravel application bootstrap for your projects

The idea is to isolate assets compilation and template pages for frontend/admin in a single, generic, reusable module.
the base is SBAdmin but updated to bootstrap 5.3


# Installation

This module is part of [Rapyd Admin](https://github.com/zofe/rapyd-admin) package


# Customizing Rapyd Admin Layout

This module will be found on app/Modules/Layout.
You can customize scss / static assets by editing the files in this folder.

then from app/Modules/Layout you can build and publish assets running:

```
cd app/Modules/Layout
npm i 
npm run dev  
```

the last command this will compile copy and replace all assets 
in your laravel public folder


# Testing routes

- /test-admin
- /test-frontend


# Usage
you can refer to two predefined layout in your rapyd-admin modules config.php es:
```
return [
    'layout' => 'layout::frontend',
];
```
or
```
return [
    'layout' => 'layout::admin',
];
```

but you can also use directly the reference in your livewire components ie:

```php
...
    public function render()
    {
        return view('knowledgebase::Admin.articles_edit')
            ->layout('layout::admin');
    }
...
```
