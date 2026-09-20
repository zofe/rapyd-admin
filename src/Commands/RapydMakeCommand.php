<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Support\Str;
use Zofe\Rapyd\Ai\GenerationLog;

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
        {--fields= : Columns of a new model, as name:type,name:type}
        {--increments : Auto-increment integer id for a new model instead of the default uuid}';

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
            $this->call('rpd:make:'.strtolower($suffix), [
                'component' => $base.$suffix,
                'model' => $this->argument('model'),
                '--module' => $this->option('module'),
                '--fields' => $this->option('fields'),
                '--increments' => (bool) $this->option('increments'),
            ]);
        }

        $entry = $log->record(base_path(), $before, [
            'module' => $this->option('module') ? Str::studly($this->option('module')) : null,
            'model' => Str::studly($this->argument('model')),
            'component' => $base,
        ]);
        if ($entry) {
            $this->comment(count($entry['files']) . ' files written (~' . number_format(GenerationLog::chars([$entry]) / 4) . ' tokens the model did not have to generate)');
        }

        return self::SUCCESS;
    }
}
