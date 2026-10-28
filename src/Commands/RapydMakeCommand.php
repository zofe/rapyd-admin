<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Zofe\Rapyd\Ai\GenerationLog;
use Zofe\Rapyd\Stubs\Facades\StubGenerator;

/**
 * Entry point of the generators. The component is either a base name (rpd:make Articles Article
 * creates ArticlesTable, ArticlesView, ArticlesEdit; ArticlesTable alone creates only the table)
 * or one of the keywords all|table|view|edit, resolved from the plural of the model.
 */
class RapydMakeCommand extends RapydMakeBaseCommand
{
    public $signature = 'rpd:make
        {component : Base name of the components (e.g. Articles, ArticlesTable) or all|table|view|edit}
        {model : The model (created with its migration when missing)}
        {--module= : Module to generate into (app/Modules/{Module})}
        {--fields= : Columns of a new model, as name:type,name:type:nullable}
        {--increments : Auto-increment integer id for a new model instead of the default uuid}
        {--seed : Fill the table with ten records from the generated factory}
        {--icon= : Font Awesome icon of the sidebar entry, e.g. newspaper (default: circle)}
        {--company : Multi-tenant: company_id on the model, each user sees the records of their company (and its children)}';

    public $description = 'rapyd command to generate components (table, view and edit) for a model';

    /** Keywords accepted as component, with the old names still recognised. */
    public const KEYWORDS = [
        'all' => ['Table', 'View', 'Edit'],
        'table' => ['Table'], 'datatable' => ['Table'],
        'view' => ['View'], 'dataview' => ['View'],
        'edit' => ['Edit'], 'dataedit' => ['Edit'],
    ];

    public function handle(): int
    {
        $this->module = $this->option('module');
        $component = $this->getComponentName();
        $keyword = strtolower($this->argument('component'));

        if (isset(self::KEYWORDS[$keyword])) {
            $base = Str::plural(Str::studly($this->argument('model')));
            $suffixes = self::KEYWORDS[$keyword];
        } else {
            $base = Str::replaceLast('Table', '', Str::replaceLast('View', '', Str::replaceLast('Edit', '', $component)));
            $suffixes = match (true) {
                str_ends_with($component, 'Table') => ['Table'],
                str_ends_with($component, 'View') => ['View'],
                str_ends_with($component, 'Edit') => ['Edit'],
                default => ['Table', 'View', 'Edit'],
            };
        }

        // what this run writes is recorded (storage/rapyd/generated.json) for the "Develop with AI" page
        $log = app(GenerationLog::class);
        $before = $log->snapshot(base_path());

        foreach ($suffixes as $suffix) {
            $options = [
                'component' => $base.$suffix,
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
                '--fields' => $this->option('fields'),
                '--increments' => (bool) $this->option('increments'),
                '--company' => (bool) $this->option('company'),
            ];
            if ($suffix === 'Table') {
                $options['--icon'] = $this->option('icon');   // the sidebar entry is written with the table
            }
            if ($this->call('rpd:make:'.strtolower($suffix), $options) !== self::SUCCESS) {
                return self::FAILURE;   // it said why; the rest would only pile errors on top
            }
        }

        $entry = $log->record(base_path(), $before, [
            'module' => $this->option('module') ? Str::studly($this->option('module')) : null,
            'model' => Str::studly($this->argument('model')),
            'component' => $base,
        ]);
        if ($entry) {
            $this->comment(count($entry['files']) . ' files written (~' . number_format(GenerationLog::chars([$entry]) / 4) . ' tokens the model did not have to generate)');
        }

        if (in_array('Table', $suffixes) && in_array('Edit', $suffixes)) {
            $this->createTest($base);
        }

        $this->seedOrSayHow();

        return self::SUCCESS;
    }

    /**
     * A feature test for the module, in tests/Feature where `php artisan test` finds it: the list behind
     * auth and the permission, the form saving what the factory makes, operator in and plain user out.
     * The agent's "verify by yourself" starts from here.
     */
    protected function createTest(string $base): void
    {
        $model = Str::studly($this->argument('model'));
        $module = $this->option('module') ? Str::studly($this->option('module')) : null;
        $modelClass = namespace_module('App\\Models', $module) . "\\{$model}";
        if (! class_exists($modelClass)) {
            return;
        }

        $dir = base_path($module ? "tests/Feature/Modules/{$module}" : 'tests/Feature');
        if (is_file("{$dir}/{$base}Test.php")) {
            return;
        }

        $resource = Str::of($base)->headline()->lower();                       // "blog posts"
        $route = (string) $resource->replace(' ', '.');                          // "blog.posts"
        $table = (new $modelClass)->getTable();
        // the first string column, looked for in the list (the uuid key is shown shortened, so not that one)
        $seeField = collect(Schema::getColumnListing($table))
            ->reject(fn ($column) => in_array($column, ['id', 'password']))
            ->first(fn ($column) => str_contains((string) Schema::getColumnType($table, $column), 'char'));

        StubGenerator::from(__DIR__.'/Templates/Test.stub', true)
            ->to($dir, true, true)
            ->as($base.'Test')
            ->withReplacers([
                'namespace' => $module ? "Tests\\Feature\\Modules\\{$module}" : 'Tests\\Feature',
                'class' => $base.'Test',
                'model' => $model,
                'modelClass' => $modelClass,
                'editClass' => namespace_module('App\\Livewire', $module) . "\\{$base}Edit",
                'editClassShort' => $base.'Edit',
                'item' => Str::singular($table),
                'listRoute' => "{$route}.table",
                'viewPermission' => "view {$resource}",
                'editPermission' => "edit {$resource}",
                'seeFirst' => $seeField ? "->assertSee(\$records->first()->{$seeField})" : '',
                'extraTests' => $this->option('company') ? $this->companyTest($model, $route) : '',
            ])
            ->save();

        $this->line("  Test written: <fg=white>php artisan test --filter {$base}Test</>");
    }

    /** With --company: an operator sees the records of their company and not the others', the list and the page alike. */
    protected function companyTest(string $model, string $route): string
    {
        return <<<PHP

    public function test_a_user_sees_the_records_of_their_own_company_only(): void
    {
        \$mine = \\Zofe\\Rapyd\\Modules\\Companies\\Models\\Company::create(['business_name' => 'Mine']);
        \$theirs = \\Zofe\\Rapyd\\Modules\\Companies\\Models\\Company::create(['business_name' => 'Theirs']);
        \$me = User::factory()->create();
        \$me->assignRole('operator');
        \$me->assignToCompany(\$mine);

        \$own = {$model}::factory()->create(['company_id' => \$mine->id]);
        \$other = {$model}::factory()->create(['company_id' => \$theirs->id]);

        \$this->actingAs(\$me)->get(route('{$route}.table'))->assertOk()->assertSee(\$own->id)->assertDontSee(\$other->id);
        \$this->actingAs(\$me)->get(route('{$route}.view', \$own))->assertOk();
        \$this->actingAs(\$me)->get(route('{$route}.view', \$other))->assertNotFound();
    }

PHP;
    }

    /** The seeder rpd:make:model wrote: run it with --seed, otherwise say how, so the table does not stay empty. */
    protected function seedOrSayHow(): void
    {
        $model = Str::studly($this->argument('model'));
        $module = $this->option('module') ? Str::studly($this->option('module')) : null;
        $seeder = ($module ? namespace_module('App\\Database\\Seeders', $module) : 'Database\\Seeders') . "\\{$model}Seeder";
        $file = base_path($module ? path_module("app/Database/Seeders/{$model}Seeder.php", $module) : "database/seeders/{$model}Seeder.php");

        if (! is_file($file)) {
            return;
        }
        if ($this->option('seed')) {
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);

            return;
        }
        $this->line("  Fill the table with ten records: <fg=white>php artisan db:seed --class=\"{$seeder}\"</>  (or rpd:make … --seed)");
    }
}
