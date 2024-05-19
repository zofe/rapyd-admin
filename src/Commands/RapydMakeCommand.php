<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make {component : all|datatable|dataview|dataedit} {model} {--module=}';

    public $description = 'rapyd command to generate components';


    public function handle()
    {
        $component = $this->getComponentName();
        $modelName = $this->getModelName();
        $this->module = $this->option('module');

        if(count($this->breadcrumbs->generate('home'))<1) {
            $this->call('rpd:make:layout');
        }

        //run correct generator
        if (str_ends_with($component, 'Table')) {

            $this->call('rpd:make:table', [
                'component' => $this->argument('component'),
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
            ]);


        } elseif (str_ends_with($component, 'View')) {

            $this->call('rpd:make:view', [
                'component' => $this->argument('component'),
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
            ]);

        } else {
            $this->warn("invalid component name. valid names ex: UsersTable, PostsTable, DocumentsView");
        }

    }



    protected function datatable($component, $model)
    {
        $this->comment('generate '.$component.' for model '.$model);

        $table = $this->getTable();
        $item = $this->getItem();
        $fields = $this->getFields();
        $whereSearch = $this->getSearchQuery($fields);

        $routename = $this->getRouteName('table');
        $routeuri = $routename->replace('.', '/');

        $componentName = $component;
        $component_name = Str::snake($componentName);

        $classPath = path_module("app/Livewire", $this->module);
        $classNamespace = namespace_module('App\\Livewire', $this->module);
        $modelNamespace = $this->getModelNamespace();

        $viewPrefix = $this->module? Str::lower($this->module).'::' : "";
        $viewPath = $viewPrefix.'livewire.'.$component_name; //$name->replace(' ', '.')

        //component

        StubGenerator::from(__DIR__.'/Templates/Livewire/DataTable.stub', true)
            ->to(base_path($classPath), true, true)
            ->as($componentName)
            ->withReplacers([
                'class' => $componentName,
                'model' => $model,
                'table' => $table,
                'view' => $viewPath,
                'whereSearch' => $whereSearch ?: 'query()',
                'modelNamespace' => $modelNamespace,
                'traitNamespace' => 'Zofe\\Rapyd\\Traits',
                'baseClass' => 'Livewire\\Component',
                'baseClassName' => 'Component',
                'traitClass' => 'WithDataTable',
                'classNamespace' => $classNamespace,
                ])
            ->save();


        //view

        $items = '';
        foreach ($fields as $field) {
            $items .= "                <td>{{ \$".$item."->$field }}</td>\n";
        }

        if(!$this->module) {
            StubGenerator::from(__DIR__.'/Templates/resources/livewire/table.blade.stub', true)
                ->to(base_path('resources/views/livewire'), true, true)
                ->as($component_name.'.blade')
                ->withReplacers([
                    'routename' => $routename,
                    'modelname' => $item,
                    'fieldNames' => Blade::render('
                @foreach($fields as $field)
                <th>{{$field}}</th>
                @endforeach
            ', compact('fields')),
                    'fieldValues' => $items,
                ])
                ->save();

        } else {
            StubGenerator::from(__DIR__.'/Templates/resources/livewire/table.blade.stub', true)
                ->to(base_path($classPath.'/views'), true, true)
                ->as($component_name.'.blade')
                ->withReplacers([
                    'routename' => $routename,
                    'modelname' => $item,
                    'fieldNames' => Blade::render('
                @foreach($fields as $field)
                <th>{{$field}}</th>
                @endforeach
            ', compact('fields')),
                    'fieldValues' => $items,
                ])
                ->save();
        }


        //route
        $strSubstitutor = (new StrReplacer([
            'class' => "\\".$classNamespace."\\".$componentName,
            'routepath' => $routeuri, //$routeuri
            'routename' => $routename,
            'homeroute' => $this->homeRoute,
        ]));
        $substituted = $strSubstitutor->replace(file_get_contents(__DIR__.'/Templates/routes/table.stub'));


        if(!$this->module) {

            file_put_contents(base_path("routes/web.php"), $substituted, FILE_APPEND);

        } else {

//            if (! file_exists(base_path(path_module("/app/Components/routes.php", $this->module)))) {
//                file_put_contents(
//                    base_path(path_module("/app/Components/routes.php", $this->module)),
//                    "<?php \n"."use Illuminate\Support\Facades\Route;\n"
//                );
//            }
//            file_put_contents(base_path(path_module("/app/routes.php", $this->module)), $substituted, FILE_APPEND);

        }

        //        //module config
        //
        //        if ($this->module && ! file_exists(base_path("app/Modules/{$this->module}/config.php"))) {
        //            StubGenerator::from(__DIR__.'/Templates/config.stub', true)
        //                ->to(base_path("app/Modules/{$this->module}"), true)
        //                ->as('config')
        //                ->withReplacers([
        //                    'module' => Str::studly($this->module),
        //                ])
        //                ->save();
        //        }

        $this->comment("component url: $routeuri ".$routename);


        $this->addNavItemIfNotExists(base_path('resources/views/components/layouts/app.blade.php'), [[
                'label' => $routename,
                'route' => $routename,
                'active' => $routeuri,
        ]]);
    }

    protected function getSearchQuery($fields)
    {
        if (($key = array_search('id', $fields)) !== false) {
            unset($fields[$key]);
        }
        if (($key = array_search('password', $fields)) !== false) {
            unset($fields[$key]);
        }

        $searchables = ['name','title','email','firstname','lastname','business_name','company_name'];
        $searchFields = collect($fields)->filter(function ($value) use ($searchables) {
            return in_array($value, $searchables);
        })->all();

        $whereSearch = '';
        foreach ($searchFields as  $index => $field) {
            if ($index === 0) {
                $whereSearch .= "where('$field', 'like', '%' . \$this->search . '%')";
            } else {
                $whereSearch .= "->orWhere('$field', 'like', '%' . \$this->search . '%')\n                ";
            }
        }
        return ltrim($whereSearch, '->');
    }

    protected function dataview($component, $model)
    {
        $this->comment('generate '.$component.' for model '.$model);

        $namespace = "\\App\\Models\\$model";

        $table = (new $namespace)->getTable();

        $fields = Schema::getColumnListing($table);
        if (($key = array_search('id', $fields)) !== false) {
            unset($fields[$key]);
        }
        if (($key = array_search('password', $fields)) !== false) {
            unset($fields[$key]);
        }
        $name = Str::of($component)->headline();


        $item = $name->before('View')->trim()->singular()->lower();
        $title = $name->before('View')->trim()->singular()->title();
        $routename = $name->before('View')
            ->replace(' ', '.')
            ->lower()
            ->replace('view', '')
            ->append('view')
        ;

        $routeuri = $routename->replace('.', '/');

        //check for table route
        $routeparent = null;
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() == $routename->replace('view','table')) {
                $routeparent =  $route->getName();
                break;
            }
        }

        if(!$routeparent) {
            $routeparent = $this->homeRoute;
        }

        $componentName = $component;
        $component_name = Str::snake($componentName);

        //dd($component, $model, $table, $name, $routename, $componentName, $component_name);

        $classPath = path_module("app/Livewire", $this->module);
        $classNamespace = namespace_module('App\\Livewire', $this->module);

        //modulare
        //$classPath = path_module("app/Components/".$name->replace(' ', '/'), $this->module);
        //$classNamespace = namespace_module('App\\Components\\'.$name->replace(' ', '\\'), $this->module);

        $viewPrefix = $this->module? Str::lower($this->module).'::' : "";
        $viewPath = $viewPrefix.'livewire.'.$component_name; //$name->replace(' ', '.')

        $modelNamespace = ! file_exists(base_path("app/Modules/{$this->module}/Models/{$model}.php")) ? 'App\\Models' : namespace_module('App\\Models', $this->module);


        StubGenerator::from(__DIR__.'/Templates/Livewire/DataView.stub', true)
            ->to(base_path($classPath), true, true)
            ->as($componentName)
            ->withReplacers([
                'class' => $componentName,
                'model' => $model,
                'table' => $table,
                'view' => $viewPath,
                'modelNamespace' => $modelNamespace,
                'baseClass' => 'Livewire\\Component',
                'baseClassName' => 'Component',
                'classNamespace' => $classNamespace,
                'item' => Str::camel($model),
            ])
            ->save();


        //view
        $items = '';
        foreach ($fields as $field) {
            $items .= "    <dt class=\"col-5\">$field</dt>\n";
            $items .= "               <dd class=\"col-7\">{{ \${$item}->{$field} }}</dd>\n";
        }


        if(!$this->module) {
            StubGenerator::from(__DIR__.'/Templates/resources/livewire/view.blade.stub', true)
                ->to(base_path('resources/views/livewire'), true, true)
                ->as($component_name.'.blade')
                ->withReplacers([
                    'routename' => $routename,
                    'modelname' => $item,
                    'title' => $title,
                    'fieldNames' => $items,
                    'routeparent' => $routeparent,
                ])
                ->save();


            //rotta/e
            $strSubstitutor = (new StrReplacer([
                'class' => "\\".$classNamespace."\\".$componentName,
                'routepath' => $routeuri, //$routeuri
                'routename' => $routename,
                'routeparent' => $routeparent,
                'modelkey' => "{{$item}:id}",
                'item' => Str::camel($model),
            ]));
            $substituted = $strSubstitutor->replace(file_get_contents(__DIR__.'/Templates/routes/view.stub'));
            file_put_contents(base_path("routes/web.php"), $substituted, FILE_APPEND);


            $table_view = base_path('resources/views/livewire/'.str_replace('view','table',$component_name).'.blade.php');
            $this->buildLinkToView($table_view, $routename);

        } else {

        }

    }


    protected function buildLinkToEdit($viewpath, $route)
    {
        $viewContent = File::get($viewpath);
        if($viewContent && strpos($route,'.edit')) {

            $precompiler = new \Zofe\Rapyd\Mechanisms\RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route]);

            File::put($viewpath, $newViewContent);
        }

    }


    public function addNavItemIfNotExists($filePath, $navItems)
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
