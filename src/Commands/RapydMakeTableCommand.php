<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeTableCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:table {component} {model} {--module=} {--table=} {--fields=}';

    public $description = 'rapyd command to generate DataTable component';


    public function handle()
    {
        $this->module = $this->option('module');

        $this->initBreadcrumb();
        $component = $this->getComponentName();
        $model = $this->getModelName();

        $componentName = $component;
        $component_name = Str::snake($componentName);

        $this->createModuleConfig();
        $this->createModel($model);

        $this->comment('generate '.$component.' for model '.$model);

        $table = $this->getTable();
        $item = $this->getItem();
        $fields = $this->getFields();
        $whereSearch = $this->getSearchQuery($fields);
        $title = $this->getTitle('table');

        $routename = $this->getRouteName('table');
        $routeuri = $routename->replace('.', '/');
        $routetitle = $title;

        $classPath = path_module("app/Livewire", $this->module);
        $viewPath = path_module("resources/views/livewire", $this->module);
        $classNamespace = namespace_module('App\\Livewire', $this->module);
        $modelNamespace = $this->getModelNamespace();
        $view = $this->getViewPath($component_name);

        //component

        StubGenerator::from(__DIR__.'/Templates/Livewire/DataTable.stub', true)
            ->to(base_path($classPath), true, true)
            ->as($componentName)
            ->withReplacers([
                'class' => $componentName,
                'model' => $model,
                'table' => $table,
                'view' => $view,
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


        StubGenerator::from(__DIR__.'/Templates/resources/livewire/table.blade.stub', true)
            ->to(base_path($viewPath), true, true)
            ->as($component_name.'.blade')
            ->withReplacers([
                'routename' => $routename,
                'modelname' => $item,
                'title' => $routetitle,
                'fieldNames' => Blade::render('
            @foreach($fields as $field)
            <th>{{$field}}</th>
            @endforeach
        ', compact('fields')),
                'fieldValues' => $items,
            ])
            ->save();



        //route
        $routePath = path_module("routes/web.php", $this->module);
        $strSubstitutor = (new StrReplacer([
            'class' => "\\".$classNamespace."\\".$componentName,
            'routepath' => $routeuri, //$routeuri
            'routename' => $routename,
            'routetitle' => $routetitle,
            'homeroute' => $this->homeRoute,
        ]));
        $substituted = $strSubstitutor->replace(File::get(__DIR__.'/Templates/routes/table.stub'));

        if ($this->module) {
            if (! File::exists(base_path($routePath))) {
                File::ensureDirectoryExists(dirname($routePath));
                File::put(base_path($routePath), "<?php \n"."use Illuminate\Support\Facades\Route;\n");
            }
        }
        File::append(base_path($routePath), $substituted);

        $this->comment("component url: $routeuri ".$routename);

        $this->addNavItemIfNotExists([[
            'label' => $routetitle,
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
        foreach ($searchFields as $index => $field) {
            if ($index === 0) {
                $whereSearch .= "where('$field', 'like', '%' . \$this->search . '%')";
            } else {
                $whereSearch .= "->orWhere('$field', 'like', '%' . \$this->search . '%')\n                ";
            }
        }

        return ltrim($whereSearch, '->');
    }

}
