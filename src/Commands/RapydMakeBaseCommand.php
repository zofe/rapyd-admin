<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Zofe\Rapyd\Breadcrumbs\BreadcrumbsMiddleware;
use Zofe\Rapyd\Breadcrumbs\Manager;
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

    protected function createModuleConfig()
    {
        $module = $this->option('module');
        if ($module && ! file_exists(base_path(path_module("app/config.php", $module)))) {

            //config
            StubGenerator::from(__DIR__.'/Templates/config.stub', true)
                ->to(base_path(path_module("app/", $module)), true, true)
                ->as('config')
                ->withReplacers([
                    'view' => $this->getViewPath('menu'),
                    'module' => ucfirst(strtolower($module)),
                ])
                ->save();

        }

        if ($module && ! file_exists(base_path(path_module("app/Views/menu.blade.php", $module)))) {

            //menu
            StubGenerator::from(__DIR__.'/Templates/resources/views/menu.blade.stub', true)
                ->to(base_path(path_module("app/Views", $module)), true, true)
                ->as('menu.blade')
                ->save();

        }

    }


    /**
     * When the model does not exist yet it is created with rpd:make:model (columns from --fields,
     * no prompt without a terminal) and its migration is run, since the generators read the table schema.
     * A new module's migrations are not registered in this process, hence the explicit path.
     */
    protected function createModel($model)
    {
        $module = $this->option('module');
        $modelClass = $this->getModelNamespace(true, false);

        if (! $modelClass) {
            $this->call('rpd:make:model', [
                'model' => $model,
                '--module' => $module,
                '--fields' => $this->option('fields'),
                '--no-interaction' => ! $this->input->isInteractive(),
            ]);

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
                $newNavItems .= '<x-rpd::nav-item icon="fas fa-fw fa-circle" label="' . htmlspecialchars($navItem['label']) . '" route="' . htmlspecialchars($navItem['route']) . '"';
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
