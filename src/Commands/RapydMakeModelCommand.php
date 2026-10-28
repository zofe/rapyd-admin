<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Zofe\Rapyd\Stubs\Facades\StubGenerator;

/**
 * Creates a model, its migration, a factory and a seeder, in the application or in a module.
 * The key is a uuid (HasUuids + ShortId, like the bundled models) unless --increments is given.
 * The columns come from --fields ("name:string,active:boolean", type defaults to string);
 * without --fields the command asks for them only when a terminal is attached,
 * so agents and CI get a table with the default columns and no hang.
 */
class RapydMakeModelCommand extends Command
{
    public $signature = 'rpd:make:model {model} {--module=}
        {--fields= : Columns as name:type,name:type (types: string text integer boolean date datetime float decimal json timestamp)}
        {--increments : Auto-increment integer id instead of the default uuid}';

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
            if (! $this->option('increments')) {
                $migrationContent = str_replace('$table->id();', "\$table->uuid('id')->primary();", $migrationContent);
            }
            File::put($migrationFile, $migrationContent);
        }
        $this->decorateModel(base_path("app/Models/{$modelName}.php"), $fields, ! $this->option('increments'));

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

        $this->createFactoryAndSeeder($modelName, $fields);

        $this->info('Model, migration, factory and seeder created.');

        return self::SUCCESS;
    }

    /**
     * A factory from the columns and a seeder that uses it, next to the migration: the table opens
     * with something in it, and a test has data to work with. The model gets HasFactory and, in a
     * module, newFactory() — Laravel looks for factories in Database\Factories only.
     */
    protected function createFactoryAndSeeder(string $modelName, array $fields): void
    {
        $module = $this->module;
        $modelClass = namespace_module('App\\Models', $module) . "\\{$modelName}";
        $factoryNamespace = $module ? namespace_module('App\\Database\\Factories', $module) : 'Database\\Factories';
        $seederNamespace = $module ? namespace_module('App\\Database\\Seeders', $module) : 'Database\\Seeders';
        $factoryPath = base_path($module ? path_module('app/Database/Factories', $module) : 'database/factories');
        $seederPath = base_path($module ? path_module('app/Database/Seeders', $module) : 'database/seeders');

        $definition = [];
        foreach ($fields as $field) {
            $definition[] = "            '{$field['fieldName']}' => " . $this->fakeFor($field['fieldName'], $field['fieldType']) . ',';
        }

        StubGenerator::from(__DIR__.'/Templates/Factory.stub', true)
            ->to($factoryPath, true, true)
            ->as($modelName.'Factory')
            ->withReplacers([
                'namespace' => $factoryNamespace,
                'model' => $modelName,
                'modelClass' => $modelClass,
                'definition' => implode("\n", $definition),
            ])
            ->save();

        StubGenerator::from(__DIR__.'/Templates/Seeder.stub', true)
            ->to($seederPath, true, true)
            ->as($modelName.'Seeder')
            ->withReplacers([
                'namespace' => $seederNamespace,
                'model' => $modelName,
                'modelClass' => $modelClass,
            ])
            ->save();

        $this->addFactoryToModel(
            base_path(path_module("app/Models/{$modelName}.php", $module)),
            $module ? "{$factoryNamespace}\\{$modelName}Factory" : null
        );
    }

    protected function addFactoryToModel(string $modelFile, ?string $factoryClass): void
    {
        $content = File::get($modelFile);
        $content = str_replace(
            "use Illuminate\\Database\\Eloquent\\Model;\n",
            "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\n",
            $content
        );

        $body = "    use HasFactory;\n";
        if ($factoryClass) {
            $body .= "\n    /** The factory lives in the module, where Laravel does not look by itself. */\n"
                . "    protected static function newFactory(): \\{$factoryClass}\n"
                . "    {\n        return \\{$factoryClass}::new();\n    }\n";
        }

        $content = preg_replace_callback('/(class \\w+ extends Model\\s*\\{\\n)/', fn ($m) => $m[1] . $body, $content, 1);
        File::put($modelFile, $content);
    }

    /** A believable value for a column: from its name when the name says something, from its type otherwise. */
    protected function fakeFor(string $name, string $type): string
    {
        $n = strtolower($name);
        $byName = match (true) {
            str_contains($n, 'email') => 'fake()->unique()->safeEmail()',
            str_contains($n, 'phone') => 'fake()->phoneNumber()',
            str_contains($n, 'url') || str_contains($n, 'website') => 'fake()->url()',
            str_contains($n, 'slug') => 'fake()->unique()->slug()',
            str_contains($n, 'city') => 'fake()->city()',
            str_contains($n, 'country') => 'fake()->country()',
            str_contains($n, 'address') => 'fake()->streetAddress()',
            str_contains($n, 'zip') || str_contains($n, 'postal') => 'fake()->postcode()',
            str_contains($n, 'title') || str_contains($n, 'subject') => 'fake()->sentence(4)',
            str_contains($n, 'business') || str_contains($n, 'company') => 'fake()->company()',
            str_ends_with($n, 'name') => 'fake()->name()',
            str_contains($n, 'status') => "fake()->randomElement(['active', 'inactive'])",
            str_contains($n, 'code') || str_contains($n, 'sku') => "fake()->unique()->bothify('??-####')",
            default => null,
        };
        if ($byName && in_array($type, ['string', 'text'])) {
            return $byName;
        }

        return match ($type) {
            'text' => 'fake()->paragraphs(2, true)',
            'integer' => 'fake()->numberBetween(1, 1000)',
            'boolean' => 'fake()->boolean()',
            'date' => 'fake()->date()',
            'datetime', 'timestamp' => "fake()->dateTimeBetween('-1 year', 'now')",
            'float', 'decimal' => 'fake()->randomFloat(2, 1, 1000)',
            'json' => "json_encode(['note' => fake()->word()])",
            default => 'fake()->words(3, true)',
        };
    }

    /**
     * The model Laravel just wrote gets what every Rapyd model has: HasUuids (ordered uuid keys) and
     * ShortId (8-char label for lists) unless integer ids were asked, and $fillable from the columns.
     */
    protected function decorateModel(string $modelFile, array $fields, bool $uuid): void
    {
        $content = File::get($modelFile);
        $body = '';
        if ($uuid) {
            $content = str_replace(
                "use Illuminate\\Database\\Eloquent\\Model;\n",
                "use Illuminate\\Database\\Eloquent\\Concerns\\HasUuids;\nuse Illuminate\\Database\\Eloquent\\Model;\nuse Zofe\\Rapyd\\Traits\\ShortId;\n",
                $content
            );
            $body .= "    use HasUuids;\n    use ShortId;\n";
        }
        if ($fields) {
            $names = implode(', ', array_map(fn ($f) => "'{$f['fieldName']}'", $fields));
            $body .= ($body ? "\n" : '') . "    protected \$fillable = [{$names}];\n";
        }
        if ($body) {
            $content = preg_replace('/(class \\w+ extends Model\\s*\\{\\n)(\\s*\\/\\/\\n)?/', '$1' . $body, $content, 1);
        }
        File::put($modelFile, $content);
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
