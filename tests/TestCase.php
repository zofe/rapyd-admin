<?php

namespace Zofe\Rapyd\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Zofe\Rapyd\RapydServiceProvider;
use Zofe\Rapyd\Tests\Http\Livewire\ArticlesEdit;
use Zofe\Rapyd\Tests\Http\Livewire\ArticlesTable;
use Zofe\Rapyd\Tests\Http\Livewire\ArticlesView;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->makeACleanSlate();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->makeACleanSlate();
        });
        Facade::setFacadeApplication(app());
        parent::setUp();

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(['ticket' => \Zofe\Rapyd\Tests\Models\Ticket::class], true);

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Zofe\\Rapyd\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Livewire::component('test-articles-table', \Zofe\Rapyd\Tests\Http\Livewire\ArticlesTable::class);
        Livewire::component('test-articles-edit', \Zofe\Rapyd\Tests\Http\Livewire\ArticlesEdit::class);
        Livewire::component('test-articles-view', \Zofe\Rapyd\Tests\Http\Livewire\ArticlesView::class);


    }

    protected function defineDatabaseMigrations(): void
    {
        // Register with the migrator (not testbench's loadMigrationsFrom) so migrate:fresh
        // runs these together with the package migrations, ordered by file name.
        $this->app['migrator']->path(__DIR__ . '/database/migrations');
    }


    protected function defineRoutes($router)
    {
        $router->group(['prefix' => 'test-demo'], function () use ($router) {
            $router->get('/', function () {
                return view('master');
            })->name('test');

            $router->get('/articles', ['as' => 'test.articles', 'uses' => ArticlesTable::class]);
            $router->get('/articles/view/{article:id}', ['as' => 'test.articles.view', 'uses' => ArticlesView::class]);
            $router->get('/articles/edit/{article:id?}', ['as' => 'test.articles.edit', 'uses' => ArticlesEdit::class]);
        });
    }

    public function makeACleanSlate()
    {
        Artisan::call('view:clear');
    }

    protected function getPackageProviders($app)
    {
        return [
            RapydServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [
            __DIR__.'/../resources/views',
            __DIR__.'/resources/views',

            resource_path('views'),
        ]);
        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('session.driver', 'file');

        $app['config']->set('auth.providers.users.model', \Zofe\Rapyd\Tests\Models\User::class);
        $app['config']->set('rapyd.search.models', [
            ['class' => \Zofe\Rapyd\Tests\Models\User::class, 'scope' => 'ssearch', 'route' => 'auth.users.view', 'label' => 'name', 'icon' => 'user', 'limit' => 5],
            ['class' => \Zofe\Rapyd\Modules\Companies\Models\Company::class, 'scope' => 'ssearch', 'route' => 'companies.view', 'label' => 'business_name', 'icon' => 'building', 'limit' => 5],
        ]);
        $app['config']->set('workflow.ticket', [
            'type' => 'state_machine',
            'marking_store' => ['type' => 'single_state', 'property' => 'status'],
            'initial_marking' => 'open',
            'supports' => [\Zofe\Rapyd\Tests\Models\Ticket::class],
            'places' => ['open' => ['metadata' => ['label' => 'Open']], 'closed' => ['metadata' => ['label' => 'Closed', 'final' => true]]],
            'transitions' => ['close' => ['from' => 'open', 'to' => 'closed', 'metadata' => ['label' => 'Close ticket']]],
        ]);
    }
}
