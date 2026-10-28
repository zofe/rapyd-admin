<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Zofe\Rapyd\Breadcrumbs\BreadcrumbsMiddleware;
use Zofe\Rapyd\Breadcrumbs\Manager;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Stubs\Facades\StubGenerator;

class RapydMakeBaseCommand extends Command
{
    protected $module;
    protected $homeRoute = 'home';
    protected $breadcrumbs;

    public function __construct(
        Manager $manager
    ) {
        parent::__construct();
        $this->breadcrumbs = $manager;
        $this->initBreadcrumb();
    }

    /**
     * The module's config.php (layout, menu, permissions) and menu.blade.php, created once.
     * Each model adds its own "view <table>" / "edit <table>" permissions, given to the operator
     * role, and AuthSeeder creates them so the generated pages are usable right away.
     */
    protected function createModuleConfig()
    {
        $module = $this->option('module');
        if (! $module) {
            return;
        }
        $config = base_path(path_module("app/config.php", $module));
        $permissions = "'view {$this->getTable()}', 'edit {$this->getTable()}'";

        if (! file_exists($config)) {
            StubGenerator::from(__DIR__.'/Templates/config.stub', true)
                ->to(base_path(path_module("app/", $module)), true, true)
                ->as('config')
                ->withReplacers([
                    'view' => $this->getViewPath('menu'),
                    'module' => ucfirst(strtolower($module)),
                    'permissions' => $permissions,
                ])
                ->save();
        } elseif (! str_contains(file_get_contents($config), "'view {$this->getTable()}'")) {
            // another model of the same module: append its permissions to both arrays
            $content = preg_replace(
                ["/('permissions'\s*=>\s*\[)/", "/('operator'\s*=>\s*\[)/"],
                '$1' . $permissions . ', ',
                file_get_contents($config),
                1
            );
            file_put_contents($config, $content);
        }
        $this->seedPermissions($config);

        if (! file_exists(base_path(path_module("app/Views/menu.blade.php", $module)))) {
            StubGenerator::from(__DIR__.'/Templates/resources/views/menu.blade.stub', true)
                ->to(base_path(path_module("app/Views", $module)), true, true)
                ->as('menu.blade')
                ->save();
        }
    }

    /** The module was created in this run, so its config is not merged yet: merge and seed by hand. */
    protected function seedPermissions(string $config): void
    {
        $module = require $config;
        if (empty($module['permissions'])) {
            return;
        }
        config(['auth.permissions' => array_values(array_unique(array_merge(config('auth.permissions', []), $module['permissions'])))]);
        foreach ($module['role_permissions'] ?? [] as $role => $permissions) {
            config(["auth.role_permissions.{$role}" => array_values(array_unique(array_merge(config("auth.role_permissions.{$role}", []), $permissions)))]);
        }
        if (Schema::hasTable('permissions')) {
            // roles and permissions only: generating a module must never create a user
            config(['rapyd.auth.seed_admin' => false]);
            $this->callSilently('db:seed', ['--class' => AuthSeeder::class, '--force' => true]);
        }
    }

    /**
     * Authorizations/<Model>Auth.php (record-level check) and Limits/<Model>Limit.php (data scoping)
     * in the module: discovered by the module loader, called by authorize() and limit().
     */
    protected function createAuthorizationAndLimit($model)
    {
        $module = $this->option('module');
        if (! $module) {
            return;
        }
        $replacers = [
            'model' => $model,
            'item' => Str::camel($model),
            'modelNamespace' => $this->getModelNamespace(),
        ];
        $bodies = $this->hasOption('company') && $this->option('company')
            ? $this->companyScopedBodies($model, Str::camel($model))
            : $this->openBodies($model, Str::camel($model));

        foreach (['Authorizations' => ['Authorization.stub', 'Auth'], 'Limits' => ['Limit.stub', 'Limit']] as $dir => [$stub, $suffix]) {
            $path = base_path(path_module("app/{$dir}", $module));
            if (file_exists("{$path}/{$model}{$suffix}.php")) {
                continue;
            }
            StubGenerator::from(__DIR__."/Templates/{$stub}", true)
                ->to($path, true, true)
                ->as($model.$suffix)
                ->withReplacers($replacers + ['namespace' => namespace_module("App\\{$dir}", $module), 'body' => $bodies[$suffix]])
                ->save();
        }
    }

    /** Without --company: every user with the permission sees and opens every record, the restriction is a comment. */
    protected function openBodies(string $model, string $item): array
    {
        return [
            'Limit' => "        // Every user with the permission sees every record. Restrict it here, e.g. by company:\n"
                . "        // if (! \$user || ! \$user->company_id) {\n"
                . "        //     {$model}::addGlobalScope('noAccess', fn (Builder \$q) => \$q->whereRaw('1 = 0'));\n"
                . "        //     return;\n"
                . "        // }\n"
                . "        // {$model}::addGlobalScope('onlyMine', fn (Builder \$q) => \$q->where('company_id', \$user->company_id));",
            'Auth' => "        // Every user with the permission can open every record. Restrict it here, e.g. by company:\n"
                . "        // return \$user && \${$item}->company_id === \$user->company_id;\n"
                . "        return (bool) \$user;",
        ];
    }

    /**
     * With --company: a user sees and opens the records of their own company and of its children,
     * as the bundled Companies do; a user without a company sees nothing. Super admins are let
     * through before this, by the stub.
     */
    protected function companyScopedBodies(string $model, string $item): array
    {
        $company = '\\Zofe\\Rapyd\\Modules\\Companies\\Models\\Company';

        return [
            'Limit' => "        // Guests and users without a company see nothing.\n"
                . "        if (! \$user || ! \$user->company_id) {\n"
                . "            {$model}::addGlobalScope('noAccess', fn (Builder \$q) => \$q->whereRaw('1 = 0'));\n\n"
                . "            return;\n"
                . "        }\n\n"
                . "        // Own company plus its children.\n"
                . "        {$model}::addGlobalScope('onlyMine', fn (Builder \$q) => \$q->where(function (\$q) use (\$user) {\n"
                . "            \$q->where('company_id', \$user->company_id)\n"
                . "                ->orWhereIn('company_id', {$company}::select('id')->where('parent_id', \$user->company_id));\n"
                . "        }));",
            'Auth' => "        // Own company plus its children, as the list does.\n"
                . "        return \$user && \$user->company_id && (\n"
                . "            \${$item}->company_id === \$user->company_id\n"
                . "            || {$company}::where('id', \${$item}->company_id)->where('parent_id', \$user->company_id)->exists()\n"
                . "        );",
        ];
    }

    /**
     * When the model does not exist yet it is created with rpd:make:model (columns from --fields,
     * no prompt without a terminal) and its migration is run, since the generators read the table schema.
     * A new module's migrations are not registered in this process, hence the explicit path.
     */
    protected function createModel($model): bool
    {
        $module = $this->option('module');
        $modelClass = $this->getModelNamespace(true, false);

        if (! $modelClass) {
            $created = $this->call('rpd:make:model', [
                'model' => $model,
                '--module' => $module,
                '--fields' => $this->option('fields'),
                '--increments' => (bool) $this->option('increments'),
                '--company' => $this->hasOption('company') && (bool) $this->option('company'),
                '--no-interaction' => ! $this->input->isInteractive(),
            ]);
            if ($created !== self::SUCCESS) {
                return false;   // the model command said why (a bad --fields, a relation to a model that is not there)
            }

            // Composer caches the miss of the class_exists() above: load the new file explicitly
            $file = base_path(path_module("app/Models/{$model}.php", $module));
            if (is_file($file) && ! class_exists(namespace_module('App\\Models', $module) . "\\{$model}", false)) {
                require_once $file;
            }

            $options = ['--force' => true];
            if ($module) {
                $options['--path'] = path_module('app/Database/Migrations', $module);
            }
            $this->call('migrate', $options);
        }

        return true;
    }

    protected function getComponentName(): string
    {
        return Str::studly($this->argument('component'));
    }
    protected function getModelNamespace($full = false, $ignore_existence = true): string
    {
        $model = $this->argument('model');
        $module = $this->option('module');
        $table = $this->option('table');

        //cerco prima il model nel modulo
        $namespace = namespace_module('App\\Models', $module);

        if ($ignore_existence) {
            return $full ? $namespace . "\\" . $model : $namespace;
        }

        if (class_exists($namespace."\\".$model)) {
            return $full ? $namespace . "\\" . $model : $namespace;
        } else {
            $namespace = 'App\\Models';
            if (class_exists($namespace."\\".$model)) {
                return $full ? $namespace . "\\" . $model : $namespace;
            }
        }

        return false;
    }

    protected function getModelName(): string
    {
        $model = $this->argument('model');
        if (! $model) {
            $model = str_replace(['Table','View','Edit'], ['',''], $this->argument('component'));
        }

        return Str::singular($model);
    }

    protected function getTable(): string
    {
        $namespace = $this->getModelNamespace(true);

        return (new $namespace)->getTable();
    }

    protected function getRouteName($type)
    {
        $name = Str::of($this->getComponentName())->headline();

        return $name->before(ucfirst(strtolower($type)))
            ->replace(' ', '.')
            ->lower()
            ->replace(strtolower($type), '')
            ->append(strtolower($type))
        ;
    }

    protected function getTitle($type, $context = 'table')
    {
        $name = Str::of($this->getComponentName())->headline();
        $title = $name->before(ucfirst(strtolower($type)))->trim();

        if ($context == 'detail') {
            return $title->singular()->title().' Detail';
        } elseif ($context == 'update') {
            return 'Update '.$title->singular()->title();
        } elseif ($context == 'create') {
            return 'Create '.$title->singular()->title();
        } else {
            return $title->plural()->title();
        }

    }

    protected function getItem()
    {
        //da capire forse ha senso usare il singolare del ComponentName (senza la parte finale)
        return Str::singular($this->getTable());
    }

    /**
     * The belongsTo behind a "<name>_id" column, when the model has the method: the relation's name,
     * the related class and table, and the column of the related table that reads as a label.
     * Null for a plain column.
     *
     * @return array{method: string, target: string, table: string, label: string}|null
     */
    protected function belongsToOf(string $modelClass, string $column): ?array
    {
        if (! str_ends_with($column, '_id') || ! class_exists($modelClass)) {
            return null;
        }
        $method = Str::camel(Str::beforeLast($column, '_id'));
        if (! method_exists($modelClass, $method)) {
            return null;
        }

        try {
            $relation = (new $modelClass)->{$method}();
        } catch (\Throwable) {
            return null;
        }
        if (! $relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
            return null;
        }
        $table = $relation->getRelated()->getTable();

        return ['method' => $method, 'target' => get_class($relation->getRelated()), 'table' => $table, 'label' => $this->labelColumnOf($table)];
    }

    /** The columns of the table as the driver describes them, read once: name => [type_name, nullable, …]. */
    protected function columns(string $table): array
    {
        return $this->columns[$table] ??= collect(Schema::getColumns($table))->keyBy('name')->all();
    }

    protected array $columns = [];

    /**
     * What a column is, for a form or a page: text, date, datetime, boolean, number or string. From the
     * driver's type name, so it holds on sqlite (varchar, datetime, tinyint) and mysql (longtext, decimal) alike.
     */
    protected function columnKind(string $table, string $field): string
    {
        $type = strtolower((string) ($this->columns($table)[$field]['type_name'] ?? Schema::getColumnType($table, $field)));

        return match (true) {
            str_contains($type, 'text') || $type === 'json' => 'text',
            $type === 'date' => 'date',
            str_contains($type, 'datetime') || str_contains($type, 'timestamp') => 'datetime',
            str_contains($type, 'bool') || $type === 'tinyint' || $type === 'tinyint(1)' => 'boolean',
            str_contains($type, 'int') || in_array($type, ['float', 'double', 'decimal', 'numeric', 'real']) => 'number',
            default => 'string',
        };
    }

    /**
     * How a column is shown on a list or a page: a boolean as a word with an icon, a date as a date
     * (with the time and "3 days ago" when it has a time), a status as a badge, a relation by its
     * label, a long text cut to one line on the list; the rest as it is.
     */
    protected function displayOf(string $modelClass, string $table, string $item, string $field, bool $inList): string
    {
        if ($relation = $this->belongsToOf($modelClass, $field)) {
            return "{{ \${$item}->{$relation['method']}?->{$relation['label']} }}";
        }

        return match ($this->columnKind($table, $field)) {
            'boolean' => "<x-rpd::bool :value=\"\${$item}->{$field}\" />",
            'datetime' => "<x-rpd::date-formatted :date=\"\${$item}->{$field}\" />",
            'date' => "<x-rpd::date-formatted :date=\"\${$item}->{$field}\" format=\"d/m/Y\" :ago=\"false\" />",
            'text' => $inList
                ? "{{ Str::limit(strip_tags((string) \${$item}->{$field}), 80) }}"
                : "{!! nl2br(e(\${$item}->{$field})) !!}",
            default => str_ends_with($field, 'status') || $field === 'state'
                ? "<span class=\"badge bg-{{ \${$item}->{$field} === 'active' ? 'success' : 'secondary' }}\">{{ \${$item}->{$field} }}</span>"
                : "{{ \${$item}->{$field} }}",
        };
    }

    /** The column of a table to show a record by: name, title…, else the first string column, else the id. */
    protected function labelColumnOf(string $table): string
    {
        $columns = Schema::getColumnListing($table);
        // business_name before name: a company has both, and reads by the former
        foreach (['business_name', 'company_name', 'name', 'title', 'label', 'email', 'code'] as $candidate) {
            if (in_array($candidate, $columns)) {
                return $candidate;
            }
        }
        foreach ($columns as $column) {
            if ($column !== 'id' && str_contains(strtolower((string) Schema::getColumnType($table, $column)), 'char')) {
                return $column;
            }
        }

        return 'id';
    }

    protected function getFields($safeForView = true, $safeForEdit = false)
    {
        $fields = Schema::getColumnListing($this->getTable());
        if ($safeForView) {
            $fields = array_diff($fields, ['id', 'password','email_verified_at','remember_token']);
        }
        if ($safeForEdit) {
            $fields = array_diff($fields, ['id','email_verified_at','remember_token','created_at','updated_at']);
        }

        return $fields;
    }

    /**
     * The layout written in the generated render(): the one of the module's config.php
     * (created with the module, layout::admin) or layout::admin outside a module.
     */
    protected function getLayout(): string
    {
        $config = $this->module ? base_path(path_module('app/config.php', $this->module)) : null;
        $layout = $config && is_file($config) ? ((require $config)['layout'] ?? null) : null;

        return $layout ?: 'layout::admin';
    }

    protected function getViewPath($component_name)
    {
        $viewPrefix = $this->module? Str::lower($this->module).'::' : "";

        return $viewPrefix.(! $this->module?'livewire.':'').$component_name;
    }

    protected function initBreadcrumb()
    {
        collect(Route::getRoutes()->getIterator())
            ->filter(function (\Illuminate\Routing\Route $route) {
                return array_key_exists(BreadcrumbsMiddleware::class, $route->action);
            })
            ->filter(function (\Illuminate\Routing\Route $route) {
                return ! $this->breadcrumbs->has($route->getName());
            })
            ->each(function (\Illuminate\Routing\Route $route) {
                $serialize = $route->action[BreadcrumbsMiddleware::class];

                /** @var SerializableClosure $callback */
                $callback = unserialize($serialize);
                $this->breadcrumbs->for($route->getName(), $callback->getClosure());
            });
    }

    /**
     * Append the sidebar entries to the module menu (app/Modules/{Module}/Views/menu.blade.php)
     * or, for components generated outside a module, to the app's resources/views/menu.blade.php
     * (included by the layout with @includeIf("menu")). The file is created only when there is
     * something to write in it.
     */
    protected function addNavItemIfNotExists($navItems)
    {
        $filePath = base_path($this->module ? path_module("app/Views/menu.blade.php", $this->module) : "resources/views/menu.blade.php");
        $content = file_exists($filePath) ? file_get_contents($filePath) : '';

        preg_match_all('/<x-rpd::nav-item[^>]*route="([^"]*)"/', $content, $matches);
        $existingRoutes = $matches[1];

        $newNavItems = '';
        foreach ($navItems as $navItem) {
            if (! in_array($navItem['route'], $existingRoutes)) {
                $newNavItems .= '<x-rpd::nav-item icon="' . htmlspecialchars($navItem['icon'] ?? 'fas fa-fw fa-circle') . '" label="' . htmlspecialchars($navItem['label']) . '" route="' . htmlspecialchars($navItem['route']) . '"';
                if (isset($navItem['active'])) {
                    $newNavItems .= ' active="' . htmlspecialchars($navItem['active']) . '"';
                }
                $newNavItems .= ' />' . PHP_EOL;
            }
        }

        if ($newNavItems === '') {
            return;
        }

        if (! is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        file_put_contents($filePath, $content . $newNavItems);
    }
}
