<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use Zofe\Rapyd\Stubs\Facades\StubGenerator;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeTableCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:table {component} {model} {--module=} {--table=} {--fields=} {--increments} {--company} {--force}
        {--icon= : Font Awesome icon of the sidebar entry, e.g. newspaper (default: circle)}';

    public $description = 'rapyd command to generate DataTable component';


    public function handle()
    {
        $this->module = $this->option('module');

        $this->initBreadcrumb();
        $component = $this->getComponentName();
        $model = $this->getModelName();

        $componentName = $component;
        $component_name = Str::snake($componentName);

        if ($this->refusesToOverwrite($componentName, $component_name)) {
            return self::FAILURE;
        }
        if (! $this->createModel($model)) {
            return self::FAILURE;
        }
        $this->createModuleConfig();
        $this->createAuthorizationAndLimit($model);

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
                'layout' => $this->getLayout(),
                'whereSearch' => $whereSearch ?: 'query()',
                'modelNamespace' => $modelNamespace,
                'traitNamespace' => 'Zofe\\Rapyd\\Traits',
                'baseClass' => 'Livewire\\Component',
                'baseClassName' => 'Component',
                'traitClass' => 'WithDataTable',
                'classNamespace' => $classNamespace,
            ])
            ->replace($this->overwrites())
            ->save();


        //view: a long text column is cut to one line in the list (the full value stays in the title and on the detail page),
        //a relation shows the related record's label, not its id
        $items = '';
        $headings = [];
        $modelClass = $this->getModelNamespace(true);
        foreach ($fields as $field) {
            $headings[$field] = ($relation = $this->belongsToOf($modelClass, $field)) ? Str::headline($relation['method']) : Str::headline($field);
            $cell = $this->displayOf($modelClass, $table, $item, $field, true);
            $items .= $this->columnKind($table, $field) === 'text' && ! $relation
                ? "                <td class=\"text-truncate\" style=\"max-width: 20rem;\" title=\"{{ Str::limit(strip_tags((string) \$".$item."->$field), 300) }}\">{$cell}</td>\n"
                : "                <td>{$cell}</td>\n";
        }


        StubGenerator::from(__DIR__.'/Templates/resources/livewire/table.blade.stub', true)
            ->to(base_path($viewPath), true, true)
            ->as($component_name.'.blade')
            ->withReplacers([
                'routename' => $routename,
                'modelname' => $item,
                'title' => $routetitle,
                // column headings as phrases ("Vat number"), translated by Lang/{locale}.json
                'fieldNames' => implode("\n", array_map(fn ($field) => "                <th>{{ __('" . $headings[$field] . "') }}</th>", $fields)),
                'fieldValues' => $items,
            ])
            ->replace($this->overwrites())
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
        $this->appendRouteOnce($routePath, $routename, $strSubstitutor->replace(File::get(__DIR__.'/Templates/routes/table.stub')));

        $this->comment("component url: $routeuri ".$routename);

        $this->addNavItemIfNotExists([[
            'label' => $routetitle,
            'route' => $routename,
            // the entry stays lit on the detail and the form too: the prefix, not the list's path
            'active' => '/' . Str::beforeLast($routeuri, '/'),
            'icon' => $this->navIcon($this->option('icon')),
        ]]);
    }

    /** "newspaper", "fa-newspaper" or a full class list: all end up as a Font Awesome class. */
    protected function navIcon(?string $icon): string
    {
        $icon = trim((string) $icon);
        if ($icon === '') {
            return 'fas fa-fw fa-circle';
        }

        return str_contains($icon, ' ') ? $icon : 'fas fa-fw fa-' . Str::after($icon, 'fa-');
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
