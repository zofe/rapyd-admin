<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
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
            'string', 'integer', 'boolean', 'text', 'date',
            'datetime', 'float', 'double', 'decimal', 'binary',
            'enum', 'json', 'longText', 'mediumText', 'time',
            'timestamp'
        ];

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

        $this->call('make:model', ['name' => $modelName]);

        $migrationName = 'create_' . Str::snake(Str::plural($modelName)) . '_table';
        $this->call('make:migration', ['name' => $migrationName]);

        $migrationFile = $this->getMigrationFile($migrationName);
        if ($migrationFile) {
            $migrationContent = File::get($migrationFile);
            $migrationContent = $this->addFieldsToMigration($migrationContent, $fields);
            File::put($migrationFile, $migrationContent);
        }

        $this->info('Model and Migration created successfully');

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
