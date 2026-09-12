<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\Modules\SampleModuleServiceProvider;
use Zofe\Rapyd\Tests\TestCase;

class ExternalModuleTest extends TestCase
{
    use DatabaseMigrations;

    /** Registered like any package provider: booted with the app, routes get their name lookups. */
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [SampleModuleServiceProvider::class]);
    }

    protected function setUp(): void
    {
        // The PSR-4 root of the sample module (a real package declares it in composer.json).
        spl_autoload_register(function ($class) {
            if (str_starts_with($class, 'App\\Modules\\Sample\\')) {
                $file = __DIR__ . '/../resources/modules/Sample/' . str_replace('\\', '/', substr($class, strlen('App\\Modules\\Sample\\'))) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }
        });

        parent::setUp();

        $this->seed(AuthSeeder::class);
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());
    }

    public function test_config_views_routes_and_livewire_components_are_registered()
    {
        $this->assertSame('sample::menu', config('sample.menu_admin'), 'config.php merged');
        $this->assertTrue(view()->exists('sample::hello'), 'views registered');
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('sample.hello'), 'routes loaded');
        $this->assertContains('sample', config('rapyd.modules'), 'module listed');

        Livewire::test('sample::hello-table')->assertSee('HELLO FROM SAMPLE MODULE');
    }

    public function test_views_next_to_the_components_and_the_workflow_file_are_loaded()
    {
        $this->assertTrue(view()->exists('sample::hello_inline'), 'Livewire dir is a view root');
        $this->assertSame(['open', 'closed'], config('workflow.sample_ticket.places'), 'workflow.php merged');
    }

    public function test_the_page_renders_inside_the_admin_layout()
    {
        $this->get(route('sample.hello'))->assertOk()->assertSee('HELLO FROM SAMPLE MODULE')->assertSee('logged as');
    }
}
