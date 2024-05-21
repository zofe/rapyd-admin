<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Breadcrumbs\Manager;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeTableCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:table {component} {model} {--module=}';

    public $description = 'rapyd command to generate DataTable component';


    public function handle()
    {
        $this->initBreadcrumb();
        $component = $this->getComponentName();
        $model = $this->getModelName();

        if(count($this->breadcrumbs->generate('home'))<1) {
            $this->call('rpd:make:layout');
        }

        $this->comment('generate '.$component.' for model '.$model);

        $table = $this->getTable();
        $item = $this->getItem();
        $fields = $this->getFields();
        $whereSearch = $this->getSearchQuery($fields);
        $title = $this->getTitle('table');

        $routename = $this->getRouteName('table');
        $routeuri = $routename->replace('.', '/');
        $routetitle = $title;

        $componentName = $component;
        $component_name = Str::snake($componentName);

        $classPath = path_module("app/Livewire", $this->module);
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
            ->to(base_path('resources/views/livewire'), true, true)
            ->as($component_name.'.blade')
            ->withReplacers([
                'routename' => $routename,
                'modelname' => $item,
                'title'     => $routetitle,
                'fieldNames' => Blade::render('
            @foreach($fields as $field)
            <th>{{$field}}</th>
            @endforeach
        ', compact('fields')),
                'fieldValues' => $items,
            ])
            ->save();



        //route
        $strSubstitutor = (new StrReplacer([
            'class' => "\\".$classNamespace."\\".$componentName,
            'routepath' => $routeuri, //$routeuri
            'routename' => $routename,
            'routetitle' => $routetitle,
            'homeroute' => $this->homeRoute,
        ]));
        $substituted = $strSubstitutor->replace(File::get(__DIR__.'/Templates/routes/table.stub'));
        File::append(base_path("routes/web.php"), $substituted);

        $this->comment("component url: $routeuri ".$routename);

        $this->addNavItemIfNotExists(base_path('resources/views/components/layouts/app.blade.php'), [[
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
        foreach ($searchFields as  $index => $field) {
            if ($index === 0) {
                $whereSearch .= "where('$field', 'like', '%' . \$this->search . '%')";
            } else {
                $whereSearch .= "->orWhere('$field', 'like', '%' . \$this->search . '%')\n                ";
            }
        }
        return ltrim($whereSearch, '->');
    }

}
