<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

/**
 * rpd:make:home in a throwaway application: the landing on "/" and the dashboard on "/admin",
 * served. The generated classes are autoloaded from the temporary app/ by hand.
 */
class MakeHomeTest extends TestCase
{
    use DatabaseMigrations;

    protected string $base;

    protected \Closure $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/rapyd-home-' . uniqid();
        File::ensureDirectoryExists($this->base . '/app/Livewire');
        File::ensureDirectoryExists($this->base . '/resources/views/livewire');
        File::ensureDirectoryExists($this->base . '/routes');
        File::put($this->base . '/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        File::put($this->base . '/composer.json', json_encode(['autoload' => ['psr-4' => ['App\\' => 'app/']]]));   // the layout reads it
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

    protected function tearDown(): void
    {
        spl_autoload_unregister($this->loader);
        File::deleteDirectory($this->base);
        parent::tearDown();
    }

    /** Generate, then register what the next boot would: the views folder and the routes file. */
    protected function makeHome(): void
    {
        $this->artisan('rpd:make:home')->assertSuccessful();
        View::addLocation($this->base . '/resources/views');
        Route::group([], $this->base . '/routes/web.php');
    }

    public function test_the_dashboard_is_behind_auth_and_the_landing_is_not()
    {
        $this->makeHome();

        $this->assertFileExists($this->base . '/app/Livewire/AdminHome.php');
        $this->assertStringContainsString("'web', 'auth'", File::get($this->base . '/routes/web.php'));

        $this->get('/')->assertOk()->assertSee('Login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_the_dashboard_shows_the_tiles_the_steps_and_the_activity_to_an_admin()
    {
        $this->seed(AuthSeeder::class);
        $this->makeHome();
        $admin = User::where('email', 'admin@laravel')->firstOrFail();

        $page = $this->actingAs($admin)->get('/admin')->assertOk();

        // tiles, each linking to its page
        $page->assertSee('Users')->assertSee(route('auth.users'))
            ->assertSee('Roles')->assertSee(route('auth.permissions'))
            ->assertSee('Activity, last 24h')->assertSee(route('log.activity'));

        // the checks of a fresh application: the admin exists, the rest is still to do
        $page->assertSee('Getting started')
            ->assertSee('First admin created')
            ->assertSee('Change the password of admin@laravel')
            ->assertSee('Generate your first module')
            ->assertSee('Your AI agent knows Rapyd Admin');

        $page->assertSee('Recent activity')->assertDontSee('Go to Dashboard');
    }

    public function test_a_user_without_permissions_gets_a_dashboard_without_tiles_or_steps()
    {
        $this->seed(AuthSeeder::class);
        $this->makeHome();
        $plain = User::create(['name' => 'Plain', 'email' => 'plain@example.com', 'password' => 'x']);

        $this->actingAs($plain)->get('/admin')->assertOk()
            ->assertDontSee('Getting started')
            ->assertDontSee(route('auth.users'))
            ->assertDontSee('Recent activity');
    }
}
