<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Breadcrumbs\Manager;
use Zofe\Rapyd\Mechanisms\RapydTagPrecompiler;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeViewCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:view {component} {model} {--module=}';

    public $description = 'rapyd command to generate DataView component';

    public function hasTable($component_name)
    {
        return File::exists(base_path('resources/views/livewire/'.str_replace('view','table',$component_name).'.blade.php'));
    }

    public function handle()
    {
        $this->initBreadcrumb();
        $component = $this->getComponentName();
        $model = $this->getModelName();
        $componentName = $component;
        $component_name = Str::snake($componentName);

        if(count($this->breadcrumbs->generate('home'))<1) {
            $this->call('rpd:make:layout');
        }

        $this->comment('generate '.$component.' for model '.$model);

        $table = $this->getTable();
        $item = $this->getItem();
        $fields = $this->getFields();

        $routename = $this->getRouteName('view');
        $routeuri = $routename->replace('.', '/');

        $routeparent = $this->hasTable($component_name)? str_replace('.view','.table', $routename) : 'home';
        $title = $this->getTitle('view');
        $title_detail = $this->getTitle('view', 'detail');



        $classPath = path_module("app/Livewire", $this->module);
        $classNamespace = namespace_module('App\\Livewire', $this->module);
        $modelNamespace = $this->getModelNamespace();
        $view = $this->getViewPath($component_name);

        //component

        StubGenerator::from(__DIR__.'/Templates/Livewire/DataView.stub', true)
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
            ])
            ->save();


        //view
        $items = '';
        foreach ($fields as $field) {
            $items .= "    <dt class=\"col-5\">$field</dt>\n";
            $items .= "               <dd class=\"col-7\">{{ \${$item}->{$field} }}</dd>\n";
        }


        StubGenerator::from(__DIR__.'/Templates/resources/livewire/view.blade.stub', true)
            ->to(base_path('resources/views/livewire'), true, true)
            ->as($component_name.'.blade')
            ->withReplacers([
                'routename' => $routename,
                'modelname' => $item,
                'title' => $title,
                'title_detail' => $title_detail,
                'fieldNames' => $items,
                'routeparent' => $routeparent,
            ])
            ->save();


        //rotta/e
        $strSubstitutor = (new StrReplacer([
            'class' => "\\".$classNamespace."\\".$componentName,
            'routepath' => $routeuri, //$routeuri
            'routename' => $routename,
            'routetitle' => $title_detail,
            'routeparent' => $routeparent,
            'modelkey' => "{{$item}}",
            'item' => Str::camel($model),
        ]));

        $substituted = $strSubstitutor->replace(File::get(__DIR__.'/Templates/routes/view.stub'));
        File::append(base_path("routes/web.php"), $substituted);

        $table_view = base_path('resources/views/livewire/'.str_replace('view','table',$component_name).'.blade.php');
        $this->buildLinkToView($table_view, $routename);
        $this->comment("component url: $routeuri ".$routename);

    }


    protected function buildLinkToView($viewpath, $route)
    {
        $viewContent = File::get($viewpath);
        if($viewContent && strpos($route,'.view')) {

            $precompiler = new RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route]);

            File::put($viewpath, $newViewContent);
        }

    }

}
