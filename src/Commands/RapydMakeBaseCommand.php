<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\SerializableClosure\SerializableClosure;
use Zofe\Rapyd\Breadcrumbs\BreadcrumbsMiddleware;
use Zofe\Rapyd\Breadcrumbs\Manager;

class RapydMakeBaseCommand extends Command
{
    protected $module;
    protected $homeRoute = 'home';
    protected $breadcrumbs;

    public function __construct(
        Manager $manager,
    ) {
        parent::__construct();
        $this->breadcrumbs = $manager;
        $this->initBreadcrumb();
    }

    protected function getComponentName(): string
    {
        return Str::studly($this->argument('component'));
    }
    protected function getModelNamespace($full = false): string
    {
        $model = $this->argument('model');
        $module = $this->option('module');

        $namespace = namespace_module('App\\Models', $module);
       // ! file_exists(base_path("app/Modules/{$this->module}/Models/{$model}.php")) ? 'App\\Models' : namespace_module('App\\Models', $this->module);
        if (!class_exists($namespace."\\".$model)) {
            $this->warn($namespace."\\".$model." doesn't exists as model");
            exit;
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
            return $title->singular()->title();
        }

    }

    protected function getItem()
    {
        //da capire forse ha senso usare il singolare del ComponentName (senza la parte finale)
        return Str::singular($this->getTable());
    }

    protected function getFields($safe = true)
    {
        $fields = Schema::getColumnListing($this->getTable());
        if($safe) {
            $fields =  array_diff($fields, ['id', 'password']);
        }
        return $fields;
    }

    protected function getViewPath($component_name)
    {
        $viewPrefix = $this->module? Str::lower($this->module).'::' : "";
        return $viewPrefix.'livewire.'.$component_name;
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

    protected function addNavItemIfNotExists($filePath, $navItems)
    {
        // Carica il contenuto del file Blade
        $content = file_get_contents($filePath);

        // Cerca tutti gli elementi x-rpd::nav-item e ottieni le route esistenti
        preg_match_all('/<x-rpd::nav-item[^>]*route="([^"]*)"/', $content, $matches);
        $existingRoutes = $matches[1];

        // Genera nuovi elementi x-rpd::nav-item se non esistono già
        $newNavItems = '';
        foreach ($navItems as $navItem) {
            if (!in_array($navItem['route'], $existingRoutes)) {
                $newNavItems .= '<x-rpd::nav-item label="' . htmlspecialchars($navItem['label']) . '" route="' . htmlspecialchars($navItem['route']) . '"';
                if (isset($navItem['active'])) {
                    $newNavItems .= ' active="' . htmlspecialchars($navItem['active']) . '"';
                }
                $newNavItems .= ' />' . PHP_EOL;
            }
        }

        // Se ci sono nuovi elementi, aggiungili prima della chiusura del tag x-rpd::sidebar
        if ($newNavItems) {
            $content = preg_replace('/(<\/x-rpd::sidebar>)/', $newNavItems . '$1', $content);
        }

        // Salva il contenuto modificato nel file Blade
        file_put_contents($filePath, $content);
    }
}
