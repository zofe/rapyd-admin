<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Mechanisms\RapydTagPrecompiler;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeEditCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:edit {component} {model} {--module=}';

    public $description = 'rapyd command to generate DataEdit component';


    public function handle()
    {
        $component = $this->getComponentName();
        $model = $this->getModelName();

        if(count($this->breadcrumbs->generate('home'))<1) {
            $this->call('rpd:make:layout');
        }

        $this->comment('generate '.$component.' for model '.$model);

        $table = $this->getTable();
        $item = $this->getItem();
        $fields = $this->getFields();

        $routename = $this->getRouteName('edit');
        $routeuri = $routename->replace('.', '/');
        $routeparent = $this->breadcrumbs->has(str_replace('.edit','.view', $routename))? str_replace('.edit','.view', $routename) : 'home';
        $routeparent_parameter = $this->breadcrumbs->has(str_replace('.edit','.view', $routename))? '[ $'.$item.'->id ]' : '[]';

        $title = $this->getTitle('edit');

        $componentName = $component;
        $component_name = Str::snake($componentName);

        $classPath = path_module("app/Livewire", $this->module);
        $classNamespace = namespace_module('App\\Livewire', $this->module);
        $modelNamespace = $this->getModelNamespace();
        $view = $this->getViewPath($component_name);

        //component
        $rules = "\n";
        foreach ($fields as $field) {
            $rules .="               '$item.$field' => 'required',\n";
        }


        StubGenerator::from(__DIR__.'/Templates/Livewire/DataEdit.stub', true)
            ->to(base_path($classPath), true, true)
            ->as($componentName)
            ->withReplacers([
                'class' => $componentName,
                'model' => $model,
                'table' => $table,
                'view' => $view,
                'modelNamespace' => $modelNamespace,
                'baseClass' => 'Livewire\\Component',
                'baseClassName' => 'Component',
                'classNamespace' => $classNamespace,
                'item' => Str::camel($model),
                'rules' => $rules,
            ])
            ->save();


        //view
        $items = "\n";

        foreach ($fields as $field) {
            $items .= "       <x-rpd::input model=\"$item.$field\" label=\"$field\" />\n";
        }

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
            'routetitle' => Str::plural($this->getModelName()),
            'routeparent' => $routeparent,
            'modelkey' => "{{$item}:id}",
            'item' => Str::camel($model),
        ]));

        $substituted = $strSubstitutor->replace(File::get(__DIR__.'/Templates/routes/edit.stub'));
        File::append(base_path("routes/web.php"), $substituted);

        $edit_view = base_path('resources/views/livewire/'.str_replace('edit','view',$component_name).'.blade.php');
        $this->buildLinkToEdit($edit_view, $routename);
        $this->comment("component url: $routeuri ".$routename);
    }


    protected function buildLinkToEdit($viewpath, $route)
    {
        $viewContent = File::get($viewpath);
        if($viewContent && strpos($route,'.edit')) {

            $precompiler = new RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route]);

            File::put($viewpath, $newViewContent);
        }

    }

}
