<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use Zofe\Rapyd\Mechanisms\RapydTagPrecompiler;

use Zofe\Rapyd\Stubs\Facades\StubGenerator;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeEditCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make:edit {component} {model} {--module=} {--table=} {--fields=} {--increments}';

    public $description = 'rapyd command to generate DataEdit component';

    public function hasTable($component_name)
    {
        // in a module the list's view is in app/Modules/<M>/Views: path_module knows, a bare path does not
        $tablePath = path_module('resources/views/livewire/'.str_replace('edit', 'table', $component_name).'.blade.php', $this->module);

        return File::exists(base_path($tablePath));
    }

    /** The columns of the table as the driver describes them, read once: name => [type_name, nullable, …]. */
    protected function columns(string $table): array
    {
        return $this->columns[$table] ??= collect(Schema::getColumns($table))->keyBy('name')->all();
    }

    protected array $columns = [];

    /**
     * What a column is, for the form: text, date, datetime, boolean, number or string. From the
     * driver's type name, so it holds on sqlite (varchar, datetime, tinyint) and mysql (longtext, decimal) alike.
     */
    protected function columnKind(string $table, string $field): string
    {
        $type = strtolower((string) ($this->columns($table)[$field]['type_name'] ?? Schema::getColumnType($table, $field)));

        return match (true) {
            str_contains($type, 'text') || $type === 'json' => 'text',
            $type === 'date' => 'date',
            str_contains($type, 'datetime') || str_contains($type, 'timestamp') => 'datetime',
            str_contains($type, 'bool') || $type === 'tinyint' || $type === 'tinyint(1)' => 'boolean',
            str_contains($type, 'int') || in_array($type, ['float', 'double', 'decimal', 'numeric', 'real']) => 'number',
            default => 'string',
        };
    }

    /**
     * The validation rules of a column: nullable or required from the schema, the type's rule, and what
     * the name says (email, url). A boolean is never required — an unticked box is a value.
     */
    /**
     * Which dropdown for a relation. x-rpd::select (native, no search) for a short, stable list: a category,
     * a type. x-rpd::select-list (TomSelect, search as you type) for a list that grows with the application —
     * users and companies always, any other on request with "<column>:belongsTo:search" in --fields.
     * Both load every row: past a few thousand, give select-list an endpoint of your own.
     */
    protected function wantsSearch(string $field, string $target): bool
    {
        if (in_array($target, [config('auth.providers.users.model'), 'Zofe\\Rapyd\\Modules\\Companies\\Models\\Company'], true)) {
            return true;
        }

        return (bool) preg_match('/(^|,)\s*' . preg_quote($field, '/') . '(:[^,:]+)*:search(:|,|$)/', (string) $this->option('fields'));
    }

    protected function rulesFor(string $table, string $field): string
    {
        $column = $this->columns($table)[$field] ?? [];
        $nullable = (bool) ($column['nullable'] ?? false);

        if ($relation = $this->belongsToOf($this->getModelNamespace(true), $field)) {
            return ($nullable ? 'nullable' : 'required') . "|exists:{$relation['table']},id";
        }
        $typeName = strtolower((string) ($column['type_name'] ?? ''));
        $kind = $this->columnKind($table, $field);
        $name = strtolower($field);

        $rules = match ($kind) {
            'boolean' => [],
            'text' => [$typeName === 'json' ? 'json' : 'string'],
            'date', 'datetime' => ['date'],
            'number' => [str_contains($typeName, 'int') ? 'integer' : 'numeric'],
            default => [match (true) {
                str_contains($name, 'email') => 'email',
                str_contains($name, 'url') || str_contains($name, 'website') => 'url',
                default => 'string',
            }, 'max:255'],
        };

        if ($kind === 'boolean') {
            return $nullable ? 'nullable|boolean' : 'boolean';
        }

        return implode('|', [$nullable ? 'nullable' : 'required', ...$rules]);
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

        if (! $this->createModel($model)) {
            return self::FAILURE;
        }
        $this->createModuleConfig();
        $this->createAuthorizationAndLimit($model);

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

        //component: the rules from the column (nullable, type, name), not "required" for everything
        $rules = "\n";
        foreach ($fields as $field) {
            $rules .= "               '$item.$field' => '" . $this->rulesFor($table, $field) . "',\n";
        }


        //view: the component of each column's type, not a text input for everything
        $items = "\n";
        $properties = '';
        $mountOptions = '';

        foreach ($fields as $field) {
            $label = Str::headline($field);

            // a relation: a dropdown of the related records, searchable when the list is the kind that grows.
            // The options are a property filled in mount(), as the hand-written forms do: the view stays
            // plain and the query is one place to scope later.
            if ($relation = $this->belongsToOf($this->getModelNamespace(true), $field)) {
                $label = Str::headline($relation['method']);
                $property = Str::plural($relation['method']);
                $properties .= "    public array \${$property} = [];\n";
                $mountOptions .= "        \$this->{$property} = \\{$relation['target']}::query()->orderBy('{$relation['label']}')->pluck('{$relation['label']}', 'id')->toArray();\n";
                $component = $this->wantsSearch($field, $relation['target']) ? 'select-list' : 'select';
                $items .= "       <x-rpd::{$component} col=\"col-12\" model=\"$item.$field\" label=\"$label\" placeholder=\"—\" :options=\"\${$property}\" />\n";

                continue;
            }

            $items .= '       ' . match ($this->columnKind($table, $field)) {
                'text' => "<x-rpd::textarea col=\"col-12\" model=\"$item.$field\" label=\"$label\" />",
                'date' => "<x-rpd::date col=\"col-12\" model=\"$item.$field\" label=\"$label\" />",
                'datetime' => "<x-rpd::datetime col=\"col-12\" model=\"$item.$field\" label=\"$label\" />",
                'boolean' => "<x-rpd::checkbox col=\"col-12\" model=\"$item.$field\" label=\"$label\" checkLabel=\"$label\" />",
                'number' => "<x-rpd::input type=\"number\" col=\"col-12\" model=\"$item.$field\" label=\"$label\" />",
                default => "<x-rpd::input col=\"col-12\" model=\"$item.$field\" label=\"$label\" />",
            } . "\n";
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

        //component (after the view: the relations found there become its properties, filled in mount())
        StubGenerator::from(__DIR__.'/Templates/Livewire/DataEdit.stub', true)
            ->to(base_path($classPath), true, true)
            ->as($componentName)
            ->withReplacers([
                'class' => $componentName,
                'model' => $model,
                'table' => $table,
                'view' => $view,
                'layout' => $this->getLayout(),
                'modelNamespace' => $modelNamespace,
                'baseClass' => 'Livewire\\Component',
                'baseClassName' => 'Component',
                'classNamespace' => $classNamespace,
                'item' => Str::camel($model),
                'rules' => $rules,
                'properties' => $properties,
                'mountOptions' => $mountOptions,
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
        if ($this->module) {
            if (! File::exists(base_path($routePath))) {
                File::ensureDirectoryExists(dirname(base_path($routePath)));
                File::put(base_path($routePath), "<?php \n"."use Illuminate\Support\Facades\Route;\n");
            }
        }
        File::append(base_path($routePath), $substituted);


        $edit_view = $viewPath.'/'.str_replace('edit', 'view', $component_name).'.blade.php';
        $table_view = $viewPath.'/'.str_replace('edit', 'table', $component_name).'.blade.php';
        $edit_class = $classPath.'/'.$componentName.'.php';

        $this->buildLinkToEdit(base_path($edit_view), $routename);
        $this->buildLinkToAdd(base_path($table_view), $routename);


        if ($routeparent_view) {
            $this->buildRedirect(base_path($edit_class), $routeparent_view, $routeparent_view_parameter);
        } elseif ($routeparent_table) {
            $this->buildRedirect(base_path($edit_class), $routeparent_table);
        }
        $this->comment("component url: $routeuri ".$routename);
    }


    protected function buildLinkToEdit($viewpath, $route)
    {
        $viewContent = File::get($viewpath);
        if ($viewContent && strpos($route, '.edit')) {

            $precompiler = new RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route, 'ref' => 'link-edit']);

            File::put($viewpath, $newViewContent);
        }

    }

    protected function buildLinkToAdd($viewpath, $route)
    {
        $viewContent = File::get($viewpath);
        if ($viewContent && strpos($route, '.edit')) {

            $precompiler = new RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route, 'ref' => 'link-add']);

            File::put($viewpath, $newViewContent);
        }

    }

    protected function buildRedirect($viewpath, $route, $route_parameter = '')
    {
        $viewContent = File::get($viewpath);
        if ($viewContent) {

            $precompiler = new RapydTagPrecompiler();
            $newViewContent = $precompiler($viewContent, ['route' => $route, 'route_parameter' => $route_parameter, 'ref' => 'redirect']);

            File::put($viewpath, $newViewContent);
        }

    }
}
