<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Zofe\Rapyd\Tests\TestCase;

/**
 * rpd:make end to end in a throwaway application: the model and its migration are created from
 * --fields without any prompt, the migration runs, and the three components are generated.
 * The generated model is autoloaded from the temporary app/ (there is no composer autoload for it).
 */
class MakeCommandTest extends TestCase
{
    protected string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/rapyd-make-' . uniqid();
        File::ensureDirectoryExists($this->base . '/app/Models');
        File::ensureDirectoryExists($this->base . '/database/migrations');
        File::ensureDirectoryExists($this->base . '/resources/views');
        File::ensureDirectoryExists($this->base . '/routes');
        File::put($this->base . '/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        File::put($this->base . '/composer.json', json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));
        $this->app->setBasePath($this->base);

        $base = $this->base;
        spl_autoload_register($this->loader = function (string $class) use ($base) {
            if (str_starts_with($class, 'App\\')) {
                $file = $base . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
                if (is_file($file)) {
                    require $file;
                }
            }
        });
    }

    protected \Closure $loader;

    protected function tearDown(): void
    {
        spl_autoload_unregister($this->loader);
        File::deleteDirectory($this->base);
        parent::tearDown();
    }

    public function test_it_generates_model_migration_and_components_in_a_module_without_prompts()
    {
        $this->artisan('rpd:make', [
            'component' => 'all',
            'model' => 'Supplier',
            '--module' => 'Suppliers',
            '--fields' => 'name:string,vat_number,active:boolean',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $module = $this->base . '/app/Modules/Suppliers';
        $this->assertStringContainsString('namespace App\Modules\Suppliers\Models;', File::get("{$module}/Models/Supplier.php"));

        $model = File::get("{$module}/Models/Supplier.php");
        $this->assertStringContainsString('use HasUuids;', $model, 'uuid keys by default');
        $this->assertStringContainsString('use ShortId;', $model);

        $migrations = File::files("{$module}/Database/Migrations");
        $this->assertCount(1, $migrations);
        $migration = File::get($migrations[0]->getPathname());
        $this->assertStringContainsString("\$table->uuid('id')->primary();", $migration);
        $this->assertStringContainsString("\$table->string('name');", $migration);
        $this->assertStringContainsString("\$table->string('vat_number');", $migration, 'type defaults to string');
        $this->assertStringContainsString("\$table->boolean('active');", $migration);

        $this->assertTrue(Schema::hasColumn('suppliers', 'vat_number'), 'the migration ran');

        foreach (['Table', 'View', 'Edit'] as $suffix) {
            $component = File::get("{$module}/Livewire/Suppliers{$suffix}.php");
            $this->assertStringContainsString('Authorize, Limit;', $component, "{$suffix}: authorization by default");
            $this->assertStringContainsString("\$this->authorize('admin|", $component);
            $this->assertStringContainsString("->layout('layout::admin')", $component);
        }
        $this->assertStringContainsString("authorize('admin|edit suppliers', \$this->supplier)", File::get("{$module}/Livewire/SuppliersEdit.php"));
        $this->assertSame(3, substr_count(File::get("{$module}/routes.php"), "middleware(['web', 'auth'])"), 'no guest access');

        $this->assertFileExists("{$module}/Views/suppliers_table.blade.php");
        $this->assertStringContainsString('vat_number', File::get("{$module}/Views/suppliers_table.blade.php"), 'columns read from the schema');
        $this->assertStringContainsString('->shortId }}', File::get("{$module}/Views/suppliers_table.blade.php"));

        $config = File::get("{$module}/config.php");
        $this->assertStringContainsString("'permissions' => ['view suppliers', 'edit suppliers']", $config);
        $this->assertStringContainsString("'operator' => ['view suppliers', 'edit suppliers']", $config);
        $this->assertContains('view suppliers', config('auth.permissions'), 'merged into auth.permissions (seeded when the permissions table exists)');
        $this->assertContains('edit suppliers', config('auth.role_permissions.operator'));
        $this->assertStringContainsString('route="suppliers.table"', File::get("{$module}/Views/menu.blade.php"));

        $this->assertStringContainsString('public static string $model = Supplier::class;', File::get("{$module}/Authorizations/SupplierAuth.php"));
        $this->assertStringContainsString('public static function limit(', File::get("{$module}/Limits/SupplierLimit.php"));
        $this->assertStringContainsString('namespace App\Modules\Suppliers\Limits;', File::get("{$module}/Limits/SupplierLimit.php"));
    }

    public function test_a_second_model_adds_its_permissions_to_the_module_config()
    {
        $this->artisan('rpd:make', ['component' => 'table', 'model' => 'Supplier', '--module' => 'Suppliers', '--fields' => 'name', '--no-interaction' => true])->assertSuccessful();
        $this->artisan('rpd:make', ['component' => 'table', 'model' => 'Contact', '--module' => 'Suppliers', '--fields' => 'name', '--no-interaction' => true])->assertSuccessful();

        $config = File::get($this->base . '/app/Modules/Suppliers/config.php');
        $this->assertStringContainsString("'permissions' => ['view contacts', 'edit contacts', 'view suppliers', 'edit suppliers']", $config);
        $this->assertStringContainsString("'operator' => ['view contacts', 'edit contacts', 'view suppliers', 'edit suppliers']", $config);
        $this->assertFileExists($this->base . '/app/Modules/Suppliers/Limits/ContactLimit.php');
    }

    public function test_increments_keeps_an_integer_id()
    {
        $this->artisan('rpd:make:model', ['model' => 'Note', '--fields' => 'title', '--increments' => true, '--no-interaction' => true])->assertSuccessful();

        $migration = File::get(File::files($this->base . '/database/migrations')[0]->getPathname());
        $this->assertStringContainsString('$table->id();', $migration);
        $this->assertStringNotContainsString('HasUuids', File::get($this->base . '/app/Models/Note.php'));
    }

    public function test_the_component_name_form_and_the_keywords_generate_the_same_components()
    {
        $this->artisan('rpd:make', ['component' => 'ArticlesTable', 'model' => 'Article', '--fields' => 'title', '--no-interaction' => true])
            ->assertSuccessful();

        $this->assertFileExists($this->base . '/app/Models/Article.php');
        $this->assertFileExists($this->base . '/app/Livewire/ArticlesTable.php');
        $this->assertFileDoesNotExist($this->base . '/app/Livewire/ArticlesView.php', 'only the table was asked');

        $this->artisan('rpd:make', ['component' => 'view', 'model' => 'Article', '--no-interaction' => true])->assertSuccessful();
        $this->assertFileExists($this->base . '/app/Livewire/ArticlesView.php', 'keyword resolved from the plural of the model');
        $this->assertCount(1, File::files($this->base . '/database/migrations'), 'the existing model is not created twice');
    }

    public function test_without_fields_and_without_a_terminal_the_table_has_only_the_default_columns()
    {
        $this->artisan('rpd:make:model', ['model' => 'Note', '--no-interaction' => true])
            ->expectsOutputToContain('No --fields given')
            ->assertSuccessful();

        $migration = File::get(File::files($this->base . '/database/migrations')[0]->getPathname());
        $this->assertStringContainsString("\$table->uuid('id')->primary();", $migration);
        $this->assertStringNotContainsString("\$table->string(", $migration);
    }

    public function test_an_unknown_type_or_column_name_in_fields_fails_before_writing_anything()
    {
        $this->artisan('rpd:make:model', ['model' => 'Note', '--fields' => 'title:varchar'])->assertFailed();
        $this->artisan('rpd:make:model', ['model' => 'Note', '--fields' => 'Title Case:string'])->assertFailed();

        $this->assertFileDoesNotExist($this->base . '/app/Models/Note.php');
        $this->assertCount(0, File::files($this->base . '/database/migrations'));
    }
}
