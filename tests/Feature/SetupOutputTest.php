<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Tests\TestCase;

/**
 * What a fresh install prints and leaves behind: the closing hints of rpd:install match what was
 * done, and rpd:make:home replaces the skeleton's welcome route instead of sitting next to it.
 */
class SetupOutputTest extends TestCase
{
    protected string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/rapyd-setup-' . uniqid();
        File::ensureDirectoryExists($this->base . '/app/Livewire');
        File::ensureDirectoryExists($this->base . '/config');
        File::ensureDirectoryExists($this->base . '/database/migrations');
        File::ensureDirectoryExists($this->base . '/resources/views/livewire');
        File::ensureDirectoryExists($this->base . '/routes');
        $this->app->setBasePath($this->base);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->base);
        parent::tearDown();
    }

    /** The skeleton of laravel/laravel, as composer create-project leaves it. */
    protected string $skeletonRoutes = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

PHP;

    public function test_make_home_replaces_the_skeleton_welcome_route()
    {
        File::put($this->base . '/routes/web.php', $this->skeletonRoutes);

        $this->artisan('rpd:make:home')->assertSuccessful();

        $routes = File::get($this->base . '/routes/web.php');
        $this->assertStringNotContainsString("view('welcome')", $routes);
        $this->assertSame(1, substr_count($routes, "Route::get('/',"), 'one route for "/", the generated home');
        $this->assertStringContainsString("Route::get('/admin',", $routes);
        $this->assertStringContainsString('use Illuminate\Support\Facades\Route;', $routes);
    }

    public function test_make_home_leaves_a_routes_file_without_the_skeleton_route_alone()
    {
        File::put($this->base . '/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\nRoute::get('/about', fn () => 'about');\n");

        $this->artisan('rpd:make:home')->assertSuccessful();

        $routes = File::get($this->base . '/routes/web.php');
        $this->assertStringContainsString("Route::get('/about'", $routes);
        $this->assertStringContainsString("Route::get('/',", $routes);
    }

    public function test_install_on_its_own_tells_the_two_real_next_steps()
    {
        $this->artisan('rpd:install', ['--no-user-model' => true])
            ->expectsOutputToContain('Next steps:')
            ->expectsOutputToContain('php artisan migrate')
            ->expectsOutputToContain('AuthSeeder')
            ->doesntExpectOutputToContain('coming soon')
            ->doesntExpectOutputToContain('laravel-assets')
            ->assertSuccessful();

        $this->assertFileExists($this->base . '/config/livewire.php');
    }

    public function test_install_inside_setup_prints_no_hints_about_what_setup_does_itself()
    {
        $this->artisan('rpd:install', ['--no-user-model' => true, '--uuid-users' => true, '--no-next-steps' => true])
            ->doesntExpectOutputToContain('Next steps:')
            ->doesntExpectOutputToContain('php artisan migrate')
            ->assertSuccessful();
    }

    public function test_the_hints_skip_the_options_that_were_already_used()
    {
        $this->artisan('rpd:install', ['--no-user-model' => true, '--uuid-users' => true, '--companies' => true])
            ->doesntExpectOutputToContain('--uuid-users')
            ->doesntExpectOutputToContain('--companies')
            ->assertSuccessful();
    }

    public function test_the_favicon_the_config_points_to_is_shipped_with_the_assets()
    {
        $this->assertFileExists(dirname(__DIR__, 2) . '/public/img/favicon.svg');
        $this->assertSame('vendor/rapyd/img/favicon.svg', config('rapyd.layout.favicon'));
    }
}
