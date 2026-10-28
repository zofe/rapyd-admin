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
        {--fields= : Columns as name:type,name:type:nullable (types: string text integer boolean date datetime float decimal json timestamp; category_id:belongsTo[:Category] for a relation)}
        {--increments : Auto-increment integer id instead of the default uuid}
        {--company : The record belongs to a company: company_id, the relation, the factory}';

    public $description = 'rapyd command to generate models';

    public const TYPES = [
        'string', 'text', 'integer', 'boolean', 'date',
        'datetime', 'float', 'decimal', 'json', 'timestamp', 'belongsTo',
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
        if ($this->option('company') && ! collect($fields)->contains('fieldName', 'company_id')) {
            $fields[] = ['fieldName' => 'company_id', 'fieldType' => 'belongsTo', 'nullable' => false, 'target' => 'Company'];
        }
        $fields = $this->resolveRelations($fields);
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

        $this->addRelations(base_path(path_module("app/Models/{$modelName}.php", $this->module)), $fields);
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
            // a relation: the related model's factory when it has one, an existing row otherwise
            // (a company is made up when there is none, so seeds and tests work on an empty database)
            $value = match (true) {
                $field['fieldType'] !== 'belongsTo' => $this->fakeFor($field['fieldName'], $field['fieldType']),
                $field['target'] === 'Zofe\\Rapyd\\Modules\\Companies\\Models\\Company' => "fn () => \\{$field['target']}::query()->inRandomOrder()->value('id') ?? \\{$field['target']}::create(['business_name' => fake()->company()])->id",
                in_array(\Illuminate\Database\Eloquent\Factories\HasFactory::class, class_uses_recursive($field['target'])) => "\\{$field['target']}::factory()",
                default => "fn () => \\{$field['target']}::query()->inRandomOrder()->value('id')",
            };
            $definition[] = "            '{$field['fieldName']}' => {$value},";
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

    /**
     * "category_id:belongsTo" (or "category:belongsTo", "author_id:belongsTo:User") points to a model that
     * has to exist already: the module's, the application's, the users model, or a bundled one. Each
     * relation field gets its class, table and whether its key is a uuid — the migration needs to know.
     */
    protected function resolveRelations(array $fields): array|false
    {
        foreach ($fields as &$field) {
            if ($field['fieldType'] !== 'belongsTo') {
                continue;
            }
            if (! str_ends_with($field['fieldName'], '_id')) {
                $field['fieldName'] .= '_id';
            }
            $name = $field['target'] ?? Str::studly(Str::singular(Str::beforeLast($field['fieldName'], '_id')));
            $class = collect([
                namespace_module('App\\Models', $this->module) . "\\{$name}",
                "App\\Models\\{$name}",
                $name === 'User' ? config('auth.providers.users.model') : null,
                "Zofe\\Rapyd\\Modules\\Companies\\Models\\{$name}",
            ])->filter()->first(fn ($candidate) => class_exists($candidate));

            if (! $class) {
                $this->error("No model {$name} for '{$field['fieldName']}': generate it first (rpd:make {$name}s {$name} --fields=...), or name it: {$field['fieldName']}:belongsTo:TheModel");

                return false;
            }
            $instance = new $class;
            $field['target'] = $class;
            $field['targetTable'] = $instance->getTable();
            $field['targetUuid'] = $instance->getKeyType() === 'string';
            $field['method'] = Str::camel(Str::beforeLast($field['fieldName'], '_id'));
        }

        return $fields;
    }

    /** One belongsTo method per relation field, before the closing brace of the class. */
    protected function addRelations(string $modelFile, array $fields): void
    {
        $relations = array_filter($fields, fn ($f) => $f['fieldType'] === 'belongsTo');
        if (! $relations) {
            return;
        }

        $content = File::get($modelFile);
        $content = str_replace(
            "use Illuminate\\Database\\Eloquent\\Model;\n",
            "use Illuminate\\Database\\Eloquent\\Model;\nuse Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;\n",
            $content
        );

        $methods = '';
        foreach ($relations as $field) {
            $methods .= "\n    public function {$field['method']}(): BelongsTo\n    {\n        return \$this->belongsTo(\\{$field['target']}::class);\n    }\n";
        }
        $content = rtrim($content);
        $content = substr($content, 0, strrpos($content, '}')) . ltrim($methods, "\n") . "}\n";
        File::put($modelFile, $content);
    }

    protected function addFactoryToModel(string $modelFile, ?string $factoryClass): void
    {
        $content = File::get($modelFile);
        $content = str_replace(
            "use Illuminate\\Database\\Eloquent\\Model;\n",
            "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\nuse Illuminate\\Database\\Eloquent\\Model;\n",
            $content
        );

        // the trait with the other traits at the top of the class, the method at the bottom
        $content = preg_replace_callback('/(class \\w+ extends Model\\s*\\{\\n)/', fn ($m) => $m[1] . "    use HasFactory;\n", $content, 1);
        if ($factoryClass) {
            $method = "\n    /** The factory lives in the module, where Laravel does not look by itself. */\n"
                . "    protected static function newFactory(): \\{$factoryClass}\n"
                . "    {\n        return \\{$factoryClass}::new();\n    }\n";
            $content = rtrim($content);
            $content = substr($content, 0, strrpos($content, '}')) . ltrim($method, "\n") . "}\n";
        }
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
        // casts: a boolean is true or false, a date is a Carbon, a decimal keeps its two places —
        // the pages print them through the components, the tests compare them without surprises.
        // Dates carry the format of the HTML input they are bound to (date, datetime-local): Livewire
        // sends the model's serialized value to the input, and a browser leaves an ISO value blank.
        $casts = [];
        foreach ($fields as $field) {
            $cast = match ($field['fieldType']) {
                'boolean' => 'boolean',
                'date' => 'date:Y-m-d',
                'datetime', 'timestamp' => 'datetime:Y-m-d\TH:i',
                'decimal' => 'decimal:2',
                'float' => 'float',
                'integer' => 'integer',
                default => null,
            };
            if ($cast) {
                $casts[] = "'{$field['fieldName']}' => '{$cast}'";
            }
        }
        if ($casts) {
            $body .= "\n    protected \$casts = [" . implode(', ', $casts) . "];\n";
        }
        if ($body) {
            $content = preg_replace('/(class \\w+ extends Model\\s*\\{\\n)(\\s*\\/\\/\\n)?/', '$1' . $body, $content, 1);
        }
        File::put($modelFile, $content);
    }

    /**
     * "name:string,vat_number,phone:string:nullable,active:boolean"
     *   => [['fieldName' => 'name', 'fieldType' => 'string', 'nullable' => false], ...].
     * A third part, "nullable", makes the column optional ("phone:nullable" works too: type defaults to string).
     * Returns false (after an error message) on an unknown type or an invalid column name.
     */
    protected function parseFields(string $spec): array|false
    {
        $fields = [];
        foreach (array_filter(array_map('trim', explode(',', $spec))) as $item) {
            $parts = array_map('trim', explode(':', $item));
            $name = array_shift($parts);
            $nullable = in_array('nullable', $parts, true);
            // "search" is read by the form generator (a searchable dropdown for the relation); here it is just not a type
            $parts = array_values(array_filter($parts, fn ($p) => $p !== '' && ! in_array($p, ['nullable', 'search'], true)));
            $type = $parts[0] ?? 'string';
            $target = $type === 'belongsTo' ? ($parts[1] ?? null) : null;
            if (! preg_match('/^[a-z][a-z0-9_]*$/', $name)) {
                $this->error("Invalid column name '{$name}' in --fields (use snake_case)");

                return false;
            }
            if (! in_array($type, self::TYPES)) {
                $this->error("Unknown type '{$type}' for '{$name}' in --fields (" . implode(', ', self::TYPES) . ')');

                return false;
            }
            $fields[] = ['fieldName' => $name, 'fieldType' => $type, 'nullable' => $nullable, 'target' => $target];
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
            $nullable = $this->confirm('Nullable?', false);
            $fields[] = compact('fieldName', 'fieldType', 'nullable');
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
            $nullable = ! empty($field['nullable']) ? '->nullable()' : '';
            if ($field['fieldType'] === 'belongsTo') {
                $foreign = $field['targetUuid'] ? 'foreignUuid' : 'foreignId';
                $fieldStrings .= "            \$table->{$foreign}('{$field['fieldName']}'){$nullable}->constrained('{$field['targetTable']}');\n";

                continue;
            }
            $fieldStrings .= "            \$table->{$field['fieldType']}('{$field['fieldName']}'){$nullable};\n";
        }

        return str_replace(
            '$table->id();',
            '$table->id();' . "\n" . rtrim($fieldStrings),
            $content
        );
    }
}
