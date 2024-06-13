<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Breadcrumbs\BreadcrumbsMiddleware;
use Zofe\Rapyd\Breadcrumbs\Manager;

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
        if($module && ! file_exists(path_module("app/config.php", $module))) {

            //config
            StubGenerator::from(__DIR__.'/Templates/config.stub', true)
                ->to(path_module("app/", $module), true, true)
                ->as('config')
                ->withReplacers([
                    'view' => $this->getViewPath('menu'),
                    'module' => ucfirst(strtolower($module)),
                ])
                ->save();

        }

        if($module && ! file_exists(path_module("app/Views/menu.blade.php", $module))) {

            //menu
            StubGenerator::from(__DIR__.'/Templates/resources/views/menu.blade.stub', true)
                ->to(path_module("app/Views", $module), true, true)
                ->as('menu.blade')
                ->save();

        }

        //global menu
        if(! file_exists(base_path('resources/views/menu.blade.php'))) {
            StubGenerator::from(__DIR__.'/Templates/resources/views/menu.blade.stub', true)
                ->to(base_path('resources/views'), true, true)
                ->as('menu.blade')
                ->save();

        }
    }

    protected function getComponentName(): string
    {
        return Str::studly($this->argument('component'));
    }
    protected function getModelNamespace($full = false): string
    {
        $model = $this->argument('model');
        $module = $this->option('module');
        $table = $this->option('table');

        //cerco prima il model nel modulo
        $namespace = namespace_module('App\\Models', $module);
        if (! class_exists($namespace."\\".$model)) {

            //  $this->warn($namespace."\\".$model." doesn't exists as model");
            $namespace = namespace_module('App\\Models');
            //se non lo trovo cerco nel namespace principale
            if(! class_exists($namespace."\\".$model)) {
                $this->warn($namespace."\\".$model." doesn't exists as model");

                if($table) {
                    //todo generare un model eloquent data la tabella
                }

                exit;
            }
        }

        return $full ? $namespace."\\".$model : $namespace;
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

        if($context == 'detail') {
            return $title->singular()->title().' Detail';
        } elseif($context == 'update') {
            return 'Update '.$title->singular()->title();
        } elseif($context == 'create') {
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
        if($safeForView) {
            $fields = array_diff($fields, ['id', 'password','email_verified_at','remember_token']);
        }
        if($safeForEdit) {
            $fields = array_diff($fields, ['email_verified_at','remember_token','created_at','updated_at']);
        }

        return $fields;
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

    protected function addNavItemIfNotExists($navItems)
    {
        $filePath = $this->module ? path_module("app/Views/menu.blade.php", $this->module) :  base_path('resources/views/menu.blade.php');
        $content = file_get_contents($filePath);

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

        file_put_contents($filePath, $newNavItems, FILE_APPEND);
    }
}
