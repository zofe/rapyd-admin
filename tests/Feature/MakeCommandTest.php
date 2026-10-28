<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

/**
 * rpd:make end to end in a throwaway application: the model and its migration are created from
 * --fields without any prompt, the migration runs, and the three components are generated.
 * The generated model is autoloaded from the temporary app/ (there is no composer autoload for it).
 */
class MakeCommandTest extends TestCase
{
    use DatabaseMigrations;

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
            '--icon' => 'truck',
            '--no-interaction' => true,
        ])->expectsOutputToContain('Fill the table with ten records')   // without --seed, the command says how
            ->expectsOutputToContain('php artisan test --filter SuppliersTest')
            ->assertSuccessful();

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
        $this->assertStringContainsString("parent('suppliers.table')->push('Create Supplier'", File::get("{$module}/routes.php"), 'the form hangs under the list in the breadcrumb');
        $this->assertStringContainsString("parent('suppliers.view', \$supplier->id)->push('Update Supplier'", File::get("{$module}/routes.php"));

        $this->assertFileExists("{$module}/Views/suppliers_table.blade.php");
        $this->assertStringContainsString('vat_number', File::get("{$module}/Views/suppliers_table.blade.php"), 'columns read from the schema');
        $this->assertStringContainsString('->shortId }}', File::get("{$module}/Views/suppliers_table.blade.php"));

        $config = File::get("{$module}/config.php");
        $this->assertStringContainsString("'permissions' => ['view suppliers', 'edit suppliers']", $config);
        $this->assertStringContainsString("'operator' => ['view suppliers', 'edit suppliers']", $config);
        $this->assertContains('view suppliers', config('auth.permissions'), 'merged into auth.permissions');
        $this->assertContains('edit suppliers', config('auth.role_permissions.operator'));
        $this->assertTrue(\Spatie\Permission\Models\Permission::where('name', 'view suppliers')->exists(), 'seeded at generation');
        $this->assertStringContainsString('route="suppliers.table"', File::get("{$module}/Views/menu.blade.php"));
        $this->assertStringContainsString('icon="fas fa-fw fa-truck"', File::get("{$module}/Views/menu.blade.php"), '--icon names the Font Awesome icon');
        $this->assertStringContainsString('active="/suppliers"', File::get("{$module}/Views/menu.blade.php"), 'lit on the list, the detail and the form');

        $log = json_decode(File::get($this->base . '/storage/rapyd/generated.json'), true);
        $this->assertCount(1, $log, 'one run recorded');
        $this->assertSame(['Suppliers', 'Supplier', 'Suppliers'], [$log[0]['module'], $log[0]['model'], $log[0]['component']]);
        $this->assertContains('app/Modules/Suppliers/Livewire/SuppliersTable.php', array_column($log[0]['files'], 'path'));
        $this->assertGreaterThan(10, count($log[0]['files']));

        $this->assertStringContainsString('public static string $model = Supplier::class;', File::get("{$module}/Authorizations/SupplierAuth.php"));
        $this->assertStringContainsString('public static function limit(', File::get("{$module}/Limits/SupplierLimit.php"));
        $this->assertStringContainsString('namespace App\Modules\Suppliers\Limits;', File::get("{$module}/Limits/SupplierLimit.php"));

        // a factory from the columns and a seeder that uses it, wired into the model
        $factory = File::get("{$module}/Database/Factories/SupplierFactory.php");
        $this->assertStringContainsString('namespace App\Modules\Suppliers\Database\Factories;', $factory);
        $this->assertStringContainsString("'name' => fake()->name(),", $factory, 'a column called name gets a name');
        $this->assertStringContainsString("'vat_number' => fake()->words(3, true),", $factory, 'a string with no better guess');
        $this->assertStringContainsString("'active' => fake()->boolean(),", $factory);
        $this->assertStringContainsString('Supplier::factory()->count(10)->create();', File::get("{$module}/Database/Seeders/SupplierSeeder.php"));
        $model = File::get("{$module}/Models/Supplier.php");
        $this->assertStringContainsString('use HasFactory;', $model);
        $this->assertStringContainsString('return \App\Modules\Suppliers\Database\Factories\SupplierFactory::new();', $model, 'Laravel would not find a factory in a module by itself');

        $supplier = \App\Modules\Suppliers\Models\Supplier::factory()->create();
        $this->assertNotEmpty($supplier->name);
        $this->assertContains($supplier->active, [0, 1, true, false]);

        // a feature test for the module, where `php artisan test` finds it
        $test = File::get($this->base . '/tests/Feature/Modules/Suppliers/SuppliersTest.php');
        $this->assertStringContainsString('namespace Tests\Feature\Modules\Suppliers;', $test);
        $this->assertStringContainsString("route('suppliers.table')", $test);
        $this->assertStringContainsString("test(SuppliersEdit::class)", $test);
        $this->assertStringContainsString("set('supplier.' . \$field", $test);
        $this->assertStringContainsString('->assertSee($records->first()->name)', $test, 'the first string column is looked for in the list');
        $this->assertStringContainsString('assertForbidden()', $test);
        $this->assertStringContainsString('view suppliers, edit suppliers', $test);
    }

    public function test_a_belongs_to_field_gives_a_relation_a_dropdown_and_the_label_in_the_list()
    {
        $this->artisan('rpd:make', [
            'component' => 'all', 'model' => 'Category', '--module' => 'Blog',
            '--fields' => 'name', '--no-interaction' => true,
        ])->assertSuccessful();

        $this->artisan('rpd:make', [
            'component' => 'all', 'model' => 'Article', '--module' => 'Blog',
            '--fields' => 'title,category_id:belongsTo,author_id:belongsTo:User:nullable,reviewer:belongsTo:User:search',
            '--no-interaction' => true,
        ])->assertSuccessful();

        $module = $this->base . '/app/Modules/Blog';

        $migration = File::get(collect(File::files("{$module}/Database/Migrations"))->first(fn ($f) => str_contains($f->getFilename(), 'create_articles_table'))->getPathname());
        $this->assertStringContainsString("\$table->foreignUuid('category_id')->constrained('categories');", $migration);
        $this->assertStringContainsString("\$table->foreignUuid('author_id')->nullable()->constrained('users');", $migration);
        $this->assertStringContainsString("\$table->foreignUuid('reviewer_id')->constrained('users');", $migration, '"reviewer" gets its _id');

        $model = File::get("{$module}/Models/Article.php");
        $this->assertStringContainsString('public function category(): BelongsTo', $model);
        $this->assertStringContainsString('return $this->belongsTo(\App\Modules\Blog\Models\Category::class);', $model);
        $this->assertStringContainsString('public function author(): BelongsTo', $model);
        $this->assertStringContainsString('public function reviewer(): BelongsTo', $model);

        $factory = File::get("{$module}/Database/Factories/ArticleFactory.php");
        $this->assertStringContainsString("'category_id' => \App\Modules\Blog\Models\Category::factory(),", $factory, 'the related factory when there is one');
        $this->assertStringContainsString("'author_id' => ", $factory);

        // the form: a plain select for a category, a searchable one for users (a list that grows) and on request
        $edit = File::get("{$module}/Views/articles_edit.blade.php");
        $this->assertStringContainsString('<x-rpd::select col="col-12" model="article.category_id" label="Category" placeholder="—" :options="$categories" />', $edit);
        $this->assertStringContainsString('<x-rpd::select-list col="col-12" model="article.author_id" label="Author" placeholder="—" :options="$authors" />', $edit);
        $this->assertStringContainsString('<x-rpd::select-list col="col-12" model="article.reviewer_id" label="Reviewer"', $edit);
        $component = File::get("{$module}/Livewire/ArticlesEdit.php");
        $this->assertStringContainsString('public array $categories = [];', $component);
        $this->assertStringContainsString("\$this->categories = \App\Modules\Blog\Models\Category::query()->orderBy('name')->pluck('name', 'id')->toArray();", $component);
        $this->assertStringContainsString("'article.category_id' => 'required|exists:categories,id',", $component);
        $this->assertStringContainsString("'article.author_id' => 'nullable|exists:users,id',", $component);

        // the list and the detail show the related record's label, not the id
        $this->assertStringContainsString('{{ $article->category?->name }}', File::get("{$module}/Views/articles_table.blade.php"));
        $this->assertStringContainsString("<th>{{ __('Category') }}</th>", File::get("{$module}/Views/articles_table.blade.php"));
        $this->assertStringContainsString('{{ $article->author?->name }}', File::get("{$module}/Views/articles_view.blade.php"));

        // and it all holds together: a factory-made article has a real category (and a reviewer: the User
        // model has no factory here, so the factory takes an existing user)
        User::create(['name' => 'Reviewer', 'email' => 'reviewer@example.com', 'password' => 'x']);
        $article = \App\Modules\Blog\Models\Article::factory()->create();
        $this->assertNotEmpty($article->category->name);
        $this->assertSame(1, \App\Modules\Blog\Models\Category::count());
    }

    /** --company: the record belongs to a company, and a user sees those of their own company (and its children) only. */
    public function test_with_company_the_module_is_multi_tenant_from_the_first_record()
    {
        $this->seed(AuthSeeder::class);
        $this->artisan('rpd:make', [
            'component' => 'all', 'model' => 'Invoice', '--module' => 'Billing',
            '--fields' => 'number,total:decimal', '--company' => true, '--no-interaction' => true,
        ])->assertSuccessful();

        $module = $this->base . '/app/Modules/Billing';
        $this->assertStringContainsString("\$table->foreignUuid('company_id')->constrained('companies');", File::get(File::files("{$module}/Database/Migrations")[0]->getPathname()));
        $this->assertStringContainsString('public function company(): BelongsTo', File::get("{$module}/Models/Invoice.php"));
        $this->assertStringContainsString("addGlobalScope('onlyMine'", File::get("{$module}/Limits/InvoiceLimit.php"));
        $this->assertStringContainsString("->orWhereIn('company_id', \Zofe\Rapyd\Modules\Companies\Models\Company::select('id')->where('parent_id', \$user->company_id))", File::get("{$module}/Limits/InvoiceLimit.php"));
        $this->assertStringContainsString('$invoice->company_id === $user->company_id', File::get("{$module}/Authorizations/InvoiceAuth.php"));
        $this->assertStringContainsString('$this->invoice->company_id = auth()->user()->company_id;', File::get("{$module}/Livewire/InvoicesEdit.php"), 'a new record gets the user\'s company');
        $this->assertStringContainsString("'invoice.company_id' => 'required|exists:companies,id',", File::get("{$module}/Livewire/InvoicesEdit.php"));
        $this->assertStringContainsString('<x-rpd::select-list col="col-12" model="invoice.company_id" label="Company"', File::get("{$module}/Views/invoices_edit.blade.php"));
        $this->assertStringContainsString('{{ $invoice->company?->business_name }}', File::get("{$module}/Views/invoices_table.blade.php"));
        $this->assertStringContainsString('test_a_user_sees_the_records_of_their_own_company_only', File::get($this->base . '/tests/Feature/Modules/Billing/InvoicesTest.php'));

        // served: two companies, an operator in each, each sees their own (the module was created after boot:
        // views, routes, Limit and Authorization are registered here by hand, as the loader does at the next boot)
        View::addNamespace('billing', "{$module}/Views");
        Route::get('/', fn () => '')->name('home')->crumbs(fn ($crumbs) => $crumbs->push('Home', '/'));
        Route::group([], "{$module}/routes.php");
        config(['auth.limits' => array_merge(config('auth.limits', []), [\App\Modules\Billing\Limits\InvoiceLimit::class])]);
        config(['auth.authorizations' => array_merge(config('auth.authorizations', []), [\App\Modules\Billing\Authorizations\InvoiceAuth::class])]);

        $acme = \Zofe\Rapyd\Modules\Companies\Models\Company::create(['business_name' => 'ACME']);
        $globex = \Zofe\Rapyd\Modules\Companies\Models\Company::create(['business_name' => 'Globex']);
        $mine = \App\Modules\Billing\Models\Invoice::factory()->create(['company_id' => $acme->id, 'number' => 'INV-ACME']);
        $theirs = \App\Modules\Billing\Models\Invoice::factory()->create(['company_id' => $globex->id, 'number' => 'INV-GLOBEX']);

        // admin first: a Limit adds global scopes to the model class, and in one test process they stay
        // (in the application every request is a fresh process)
        $admin = User::where('email', 'admin@laravel')->firstOrFail();
        $this->actingAs($admin)->get('/invoices/table')->assertOk()->assertSee('INV-ACME')->assertSee('INV-GLOBEX');

        $anna = User::create(['name' => 'Anna', 'email' => 'anna@example.com', 'password' => 'x']);
        $anna->assignRole('operator');
        $anna->assignToCompany($acme);
        $this->actingAs($anna)->get('/invoices/table')->assertOk()->assertSee('INV-ACME')->assertDontSee('INV-GLOBEX');
        $this->actingAs($anna)->get("/invoices/view/{$mine->id}")->assertOk();
        $this->actingAs($anna)->get("/invoices/view/{$theirs->id}")->assertNotFound();

        $nobody = User::create(['name' => 'Nobody', 'email' => 'nobody@example.com', 'password' => 'x']);
        $nobody->assignRole('operator');   // the permission, but no company: nothing to see
        $this->actingAs($nobody)->get('/invoices/table')->assertOk()->assertDontSee('INV-ACME')->assertDontSee('INV-GLOBEX');
    }

    public function test_a_belongs_to_to_a_model_that_does_not_exist_is_refused_with_a_hint()
    {
        $this->artisan('rpd:make', [
            'component' => 'all', 'model' => 'Order', '--module' => 'Shop',
            '--fields' => 'total:decimal,customer_id:belongsTo', '--no-interaction' => true,
        ])->expectsOutputToContain('No model Customer')->assertFailed();
    }

    public function test_with_seed_the_table_opens_with_ten_records()
    {
        $this->artisan('rpd:make', [
            'component' => 'all',
            'model' => 'Customer',
            '--module' => 'Customers',
            '--fields' => 'company_name,email,status,notes:text,score:integer,signed_at:datetime,active:boolean,phone:string:nullable,website:nullable',
            '--seed' => true,
            '--no-interaction' => true,
        ])->doesntExpectOutputToContain('Fill the table')->assertSuccessful();

        $this->assertSame(10, \App\Modules\Customers\Models\Customer::count());

        $customer = \App\Modules\Customers\Models\Customer::first();
        $this->assertStringContainsString('@', $customer->email, 'a column called email gets an email');
        $this->assertContains($customer->status, ['active', 'inactive']);
        $this->assertNotEmpty($customer->notes);
        $this->assertIsNumeric($customer->score);
        $this->assertNotEmpty($customer->signed_at);
        $this->assertNotEmpty($customer->company_name);

        // the form gets the component of each column's type, not a text input for everything
        $edit = File::get($this->base . '/app/Modules/Customers/Views/customers_edit.blade.php');
        $this->assertStringContainsString('<x-rpd::textarea col="col-12" model="customer.notes"', $edit);
        $this->assertStringContainsString('<x-rpd::datetime col="col-12" model="customer.signed_at"', $edit);
        $this->assertStringContainsString('<x-rpd::checkbox col="col-12" model="customer.active"', $edit);
        $this->assertStringContainsString('<x-rpd::input type="number" col="col-12" model="customer.score"', $edit);
        $this->assertStringContainsString('<x-rpd::input col="col-12" model="customer.email"', $edit);
        // the rules follow the column: nullable or required, the type, what the name says
        $rules = File::get($this->base . '/app/Modules/Customers/Livewire/CustomersEdit.php');
        $this->assertStringContainsString("'customer.active' => 'boolean',", $rules, 'an unticked box is not a missing value');
        $this->assertStringContainsString("'customer.email' => 'required|email|max:255',", $rules);
        $this->assertStringContainsString("'customer.website' => 'nullable|url|max:255',", $rules);
        $this->assertStringContainsString("'customer.phone' => 'nullable|string|max:255',", $rules);
        $this->assertStringContainsString("'customer.notes' => 'required|string',", $rules);
        $this->assertStringContainsString("'customer.score' => 'required|integer',", $rules);
        $this->assertStringContainsString("'customer.signed_at' => 'required|date',", $rules);

        $migration = File::get(File::files($this->base . '/app/Modules/Customers/Database/Migrations')[0]->getPathname());
        $this->assertStringContainsString("\$table->string('phone')->nullable();", $migration);
        $this->assertStringContainsString("\$table->string('website')->nullable();", $migration, '"website:nullable" alone: type defaults to string');
        $this->assertStringContainsString("\$table->string('email');", $migration);

        // the list: a long text cut to one line, a boolean as a word, a date as a date, a status as a badge
        $table = File::get($this->base . '/app/Modules/Customers/Views/customers_table.blade.php');
        $this->assertStringContainsString('Str::limit(strip_tags((string) $customer->notes), 80)', $table);
        $this->assertStringContainsString('<td>{{ $customer->email }}</td>', $table);
        $this->assertStringContainsString('<td><x-rpd::bool :value="$customer->active" /></td>', $table);
        $this->assertStringContainsString('<td><x-rpd::date-formatted :date="$customer->signed_at" /></td>', $table);
        $this->assertStringContainsString('<td><x-rpd::date-formatted :date="$customer->created_at" /></td>', $table);
        $this->assertStringContainsString("<span class=\"badge bg-{{ \$customer->status === 'active' ? 'success' : 'secondary' }}\">{{ \$customer->status }}</span>", $table);
        $view = File::get($this->base . '/app/Modules/Customers/Views/customers_view.blade.php');
        $this->assertStringContainsString('<x-rpd::bool :value="$customer->active" />', $view);
        $this->assertStringContainsString('{!! nl2br(e($customer->notes)) !!}', $view, 'the page keeps the whole text, with its line breaks');
        $this->assertStringContainsString("protected \$casts = ['score' => 'integer', 'signed_at' => 'datetime', 'active' => 'boolean'];", File::get($this->base . '/app/Modules/Customers/Models/Customer.php'));
        $this->assertStringContainsString('icon="fas fa-fw fa-circle"', File::get($this->base . '/app/Modules/Customers/Views/menu.blade.php'), 'no --icon: the default');
    }

    /**
     * The generated pages, served: the module was created after boot, so its views and routes are
     * registered here by hand (the module loader does it at the next boot).
     */
    public function test_the_generated_pages_are_served_only_to_users_with_the_permission()
    {
        $this->seed(AuthSeeder::class);
        $this->artisan('rpd:make', [
            'component' => 'all', 'model' => 'Vendor', '--module' => 'Vendors',
            '--fields' => 'name,vat_number', '--no-interaction' => true,
        ])->assertSuccessful();

        $module = $this->base . '/app/Modules/Vendors';
        View::addNamespace('vendors', "{$module}/Views");
        Route::get('/', fn () => '')->name('home')->crumbs(fn ($crumbs) => $crumbs->push('Home', '/'));   // the app home, parent of the generated breadcrumbs
        Route::group([], "{$module}/routes.php");

        $vendor = \App\Modules\Vendors\Models\Vendor::create(['name' => 'ACME Srl', 'vat_number' => 'IT01234567890']);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $vendor->id, 'uuid key');

        $this->get('/vendors/table')->assertRedirect('/login');
        $this->get("/vendors/view/{$vendor->id}")->assertRedirect('/login');

        $admin = User::where('email', 'admin@laravel')->firstOrFail();
        $this->actingAs($admin)->get('/vendors/table')
            ->assertOk()
            ->assertSee($vendor->shortId)
            ->assertSee("/vendors/view/{$vendor->id}", false)
            ->assertDontSee("/vendors/view/{$vendor->shortId}", false);
        $this->actingAs($admin)->get("/vendors/view/{$vendor->id}")->assertOk()->assertSee('ACME Srl');
        $this->actingAs($admin)->get("/vendors/edit/{$vendor->id}")->assertOk();

        $operator = User::create(['name' => 'Anna', 'email' => 'anna@example.com', 'password' => 'secret']);
        $operator->assignRole('operator');
        $this->actingAs($operator)->get('/vendors/table')->assertOk()->assertSee('ACME Srl');
        $this->actingAs($operator)->get("/vendors/edit/{$vendor->id}")->assertOk();

        $customer = User::create(['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer)->get('/vendors/table')->assertForbidden();
        $this->actingAs($customer)->get("/vendors/view/{$vendor->id}")->assertForbidden();
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
