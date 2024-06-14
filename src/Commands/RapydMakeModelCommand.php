<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RapydMakeModelCommand extends Command
{
    public $signature = 'rpd:make:model {model} {--module=}';

    public $description = 'rapyd command to generate models';

    public $module;

    public function handle()
    {
        $this->module = $this->option('module');

        $modelName = $this->argument('model');

        $fields = [];
        $fieldTypes = [
            'string', 'text', 'integer', 'boolean', 'date',
            'datetime', 'float', 'decimal', 'json', 'timestamp',
        ];

        $this->comment('No Model ' . $modelName.' found, start creation...');

        while (true) {
            $fieldName = $this->ask('Field Name? (empty to end)');
            if (empty($fieldName)) {
                break;
            }
            $fieldType = $this->choice(
                'Field Type?',
                $fieldTypes,
                0
            );
            $fields[] = compact('fieldName', 'fieldType');
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

        if($this->module) {
            $migrationName = basename($migrationFile);
            $migration_from = base_path("database/migrations/$migrationName");
            $migration_to = base_path(path_module("app/Database/Migrations/{$migrationName}", $this->module));
            $model_from = base_path("app/Models/{$modelName}.php");
            $model_to =  base_path(path_module("app/Models/{$modelName}.php", $this->module));

            File::ensureDirectoryExists(dirname($migration_to), 0755, true);
            File::ensureDirectoryExists(dirname($model_to), 0755, true);

            File::move($migration_from, $migration_to);
            File::move($model_from, $model_to);

            $content = File::get($model_to);
            $updatedContent = str_replace('namespace App\\Models;', "namespace App\\Modules\\{$this->module}\\Models;", $content);
            File::put($model_to, $updatedContent);

        }

        $this->info('Model and Migration created successfully, you can run `php artisan migrate` to create the table.');
        exit;
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
            $fieldStrings .= "\$table->{$field['fieldType']}('{$field['fieldName']}');\n";
        }

        return str_replace(
            '$table->id();',
            '$table->id();' . "\n" . $fieldStrings,
            $content
        );
    }


}
