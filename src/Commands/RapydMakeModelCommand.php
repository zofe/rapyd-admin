<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Creates a model and its migration, in the application or in a module.
 * The columns come from --fields ("name:string,active:boolean", type defaults to string);
 * without --fields the command asks for them only when a terminal is attached,
 * so agents and CI get a table with the default columns and no hang.
 */
class RapydMakeModelCommand extends Command
{
    public $signature = 'rpd:make:model {model} {--module=} {--fields= : Columns as name:type,name:type (types: string text integer boolean date datetime float decimal json timestamp)}';

    public $description = 'rapyd command to generate models';

    public const TYPES = [
        'string', 'text', 'integer', 'boolean', 'date',
        'datetime', 'float', 'decimal', 'json', 'timestamp',
    ];

    public $module;

    public function handle(): int
    {
        $this->module = $this->option('module');

        $modelName = $this->argument('model');

        $this->comment('No Model ' . $modelName.' found, start creation...');

        $fields = $this->option('fields') !== null ? $this->parseFields($this->option('fields')) : $this->askFields();
        if ($fields === false) {
            return self::FAILURE;
        }

        $migrationName = 'create_' . Str::snake(Str::plural($modelName)) . '_table';

        Artisan::call('make:model', ['name' => $modelName, '--quiet' => true]);
        Artisan::call('make:migration', ['name' => $migrationName, '--quiet' => true]);


        $migrationFile = $this->getMigrationFile($migrationName);
        if ($migrationFile) {
            $migrationContent = File::get($migrationFile);
            $migrationContent = $this->addFieldsToMigration($migrationContent, $fields);
            File::put($migrationFile, $migrationContent);
        }

        if ($this->module) {
            $migrationName = basename($migrationFile);
            $migration_from = base_path("database/migrations/$migrationName");
            $migration_to = base_path(path_module("app/Database/Migrations/{$migrationName}", $this->module));
            $model_from = base_path("app/Models/{$modelName}.php");
            $model_to = base_path(path_module("app/Models/{$modelName}.php", $this->module));

            File::ensureDirectoryExists(dirname($migration_to), 0755, true);
            File::ensureDirectoryExists(dirname($model_to), 0755, true);

            File::move($migration_from, $migration_to);
            File::move($model_from, $model_to);

            $content = File::get($model_to);
            $updatedContent = str_replace('namespace App\\Models;', "namespace App\\Modules\\{$this->module}\\Models;", $content);
            File::put($model_to, $updatedContent);

        }

        $this->info('Model and Migration created successfully, you can run `php artisan migrate` to create the table.');

        return self::SUCCESS;
    }

    /**
     * "name:string,vat_number,active:boolean" => [['fieldName' => 'name', 'fieldType' => 'string'], ...].
     * Returns false (after an error message) on an unknown type or an invalid column name.
     */
    protected function parseFields(string $spec): array|false
    {
        $fields = [];
        foreach (array_filter(array_map('trim', explode(',', $spec))) as $item) {
            [$name, $type] = array_pad(explode(':', $item, 2), 2, 'string');
            $name = trim($name);
            $type = trim($type) ?: 'string';
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                $this->error("Invalid column name '{$name}' in --fields (use snake_case)");

                return false;
            }
            if (! in_array($type, self::TYPES)) {
                $this->error("Unknown type '{$type}' for '{$name}' in --fields (" . implode(', ', self::TYPES) . ')');

                return false;
            }
            $fields[] = ['fieldName' => $name, 'fieldType' => $type];
        }

        return $fields;
    }

    /** Interactive fallback: without a terminal the table gets only the default columns. */
    protected function askFields(): array
    {
        $hasTerminal = defined('STDIN') && function_exists('stream_isatty') && @stream_isatty(STDIN);
        if (! $this->input->isInteractive() || ! $hasTerminal) {
            $this->line('No --fields given: the migration has only id and timestamps (add columns with --fields=name:type,...)');

            return [];
        }

        $fields = [];
        while (true) {
            $fieldName = $this->ask('Field Name? (empty to end)');
            if (empty($fieldName)) {
                break;
            }
            $fieldType = $this->choice('Field Type?', self::TYPES, 0);
            $fields[] = compact('fieldName', 'fieldType');
        }

        return $fields;
    }

    private function getMigrationFile($migrationName)
    {
        $migrationPath = database_path('migrations');
        $files = File::files($migrationPath);
        foreach ($files as $file) {
            if (Str::contains($file->getFilename(), $migrationName)) {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function addFieldsToMigration($content, $fields)
    {
        $fieldStrings = '';
        foreach ($fields as $field) {
            $fieldStrings .= "            \$table->{$field['fieldType']}('{$field['fieldName']}');\n";
        }

        return str_replace(
            '$table->id();',
            '$table->id();' . "\n" . rtrim($fieldStrings),
            $content
        );
    }
}
