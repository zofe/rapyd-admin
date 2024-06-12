<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Touhidurabir\StubGenerator\Facades\StubGenerator;

use Zofe\Rapyd\Mechanisms\RapydTagPrecompiler;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeEditCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:edit {component} {model} {--module=} {--table=} {--fields=}';

    public $description = 'rapyd command to generate DataEdit component';

    public function hasTable($component_name)
    {
        return File::exists(base_path('resources/views/livewire/'.str_replace('edit', 'table', $component_name).'.blade.php'));
    }

    public function hasView($component_name)
    {
        $viewPath = path_module('resources/views/livewire/'.str_replace('edit', 'view', $component_name).'.blade.php', $this->module);

        return File::exists(base_path($viewPath));
    }

    public function handle()
    {
        $this->module = $this->option('module');

        $this->initBreadcrumb();
        $component = $this->getComponentName();
        $model = $this->getModelName();

        $componentName = $component;
        $component_name = Str::snake($componentName);

        if(count($this->breadcrumbs->generate('home')) < 1) {
            $this->call('rpd:make:layout');
        }

        $this->comment('generate '.$component.' for model '.$model);

        $table = $this->getTable();
        $item = $this->getItem();
        $fields = $this->getFields(false, true);

        $routename = $this->getRouteName('edit');
        $routeuri = $routename->replace('.', '/');

        $routeparent_table = $this->hasTable($component_name)? str_replace('.edit', '.table', $routename) : 'home';
        $routeparent_view = $this->hasView($component_name)? str_replace('.edit', '.view', $routename) : 'home';
        $routeparent_view_parameter = $this->hasView($component_name)? '$'.$item.'->id' : '[]';

        $title = $this->getTitle('edit');
        $title_create = $this->getTitle('edit', 'create');
        $title_update = $this->getTitle('edit', 'update');


        $classPath = path_module("app/Livewire", $this->module);
        $viewPath = path_module("resources/views/livewire", $this->module);
        $classNamespace = namespace_module('App\\Livewire', $this->module);
        $modelNamespace = $this->getModelNamespace();
        $view = $this->getViewPath($component_name);

        //component
        $rules = "\n";
        foreach ($fields as $field) {
            $rules .= "               '$item.$field' => 'required',\n";
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
            $items .= "       <x-rpd::input col=\"col-12\" model=\"$item.$field\" label=\"$field\" />\n";
        }

        StubGenerator::from(__DIR__.'/Templates/resources/livewire/edit.blade.stub', true)
            ->to(base_path($viewPath), true, true)
            ->as($component_name.'.blade')
            ->withReplacers([
                'routename' => $routename,
                'modelname' => $item,
                'title' => $title,
                'title_create' => $title_create,
                'title_update' => $title_update,
                'fieldNames' => $items,
                'routeparent_table' => $routeparent_table,
                'routeparent_view' => $routeparent_view,
                'routeparent_view_parameter' => $routeparent_view_parameter,
            ])
            ->save();


        //rotta/e
        $routePath = path_module("routes/web.php", $this->module);
        $strSubstitutor = (new StrReplacer([
            'class' => "\\".$classNamespace."\\".$componentName,
            'routepath' => $routeuri, //$routeuri
            'routename' => $routename,
            'routetitle_update' => $title_update,
            'routetitle_create' => $title_create,
            'routeparent_table' => $routeparent_table,
            'routeparent_view' => $routeparent_view,
            'routeparent_view_parameter' => $routeparent_view_parameter,

            'modelkey' => "{{$item}?}",
            'item' => Str::camel($model),
        ]));

        $substituted = $strSubstitutor->replace(File::get(__DIR__.'/Templates/routes/edit.stub'));
        if($this->module) {
            if (! File::exists(base_path($routePath))) {
                File::ensureDirectoryExists(dirname($routePath));
                File::put(base_path($routePath), "<?php \n"."use Illuminate\Support\Facades\Route;\n");
            }
        }
        File::append(base_path($routePath), $substituted);


        $edit_view = $viewPath.'/'.str_replace('edit', 'view', $component_name).'.blade.php';

        $this->buildLinkToEdit($edit_view, $routename);
        $this->comment("component url: $routeuri ".$routename);
    }


    protected function buildLinkToEdit($viewpath, $route)
    {
        $viewContent = File::get($viewpath);
        if($viewContent && strpos($route, '.edit')) {

            $precompiler = new RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route]);

            File::put($viewpath, $newViewContent);
        }

    }

}
