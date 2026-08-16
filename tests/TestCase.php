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

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Zofe\\Rapyd\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        Livewire::component('test-articles-table', \Zofe\Rapyd\Tests\Http\Livewire\ArticlesTable::class);
        Livewire::component('test-articles-edit', \Zofe\Rapyd\Tests\Http\Livewire\ArticlesEdit::class);
        Livewire::component('test-articles-view', \Zofe\Rapyd\Tests\Http\Livewire\ArticlesView::class);


        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
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

        // Disable bundled modules that have migrations depending on framework tables
        // (Companies adds company_id to users which doesn't exist in testbench in-memory DB)
        $app['config']->set('rapyd.companies.enabled', false);
    }
}
