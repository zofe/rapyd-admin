<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;
use Zofe\Rapyd\Tests\Themes\DemoThemeServiceProvider;

class ThemeTest extends TestCase
{
    use DatabaseMigrations;

    protected function loginAsAdmin(): void
    {
        $this->seed(AuthSeeder::class);
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());
    }

    public function test_bundled_layout_fulfils_the_contract()
    {
        $this->assertSame(0, Artisan::call('rpd:theme:check'));
    }

    public function test_demo_theme_fulfils_the_contract()
    {
        $this->assertSame(0, Artisan::call('rpd:theme:check', ['path' => __DIR__ . '/../resources/themes/demo/resources/views']));
    }

    public function test_a_theme_missing_regions_fails_the_check()
    {
        $dir = sys_get_temp_dir() . '/rapyd-broken-theme';
        @mkdir($dir, 0777, true);
        file_put_contents("$dir/app.blade.php", '<html>@yield("main")</html>');

        $this->assertSame(1, Artisan::call('rpd:theme:check', ['path' => $dir]));
        $this->assertStringContainsString('MISSING admin.blade.php', Artisan::output());
    }

    public function test_default_assets_and_layout_without_a_theme()
    {
        $this->loginAsAdmin();

        $this->assertStringContainsString('vendor/rapyd/rapyd.css', Blade::render('@rapydStyles'));
        $this->get(route('auth.users'))->assertOk()->assertDontSee('DEMO THEME');
    }

    public function test_active_theme_overrides_layout_views_and_assets()
    {
        config(['rapyd.theme' => 'demo']);
        $this->app->register(DemoThemeServiceProvider::class);
        $this->loginAsAdmin();

        $this->assertStringContainsString('vendor/themes/demo/rapyd.css', Blade::render('@rapydStyles'));
        $this->assertStringContainsString('vendor/themes/demo/rapyd.js', Blade::render('@rapydScripts'));
        $this->get(route('auth.users'))->assertOk()->assertSee('DEMO THEME');
        $this->assertSame(0, Artisan::call('rpd:theme:check'));
    }

    public function test_registered_but_inactive_theme_changes_nothing()
    {
        $this->app->register(DemoThemeServiceProvider::class);
        $this->loginAsAdmin();

        $this->assertStringContainsString('vendor/rapyd/rapyd.css', Blade::render('@rapydStyles'));
        $this->get(route('auth.users'))->assertOk()->assertDontSee('DEMO THEME');
    }

    public function test_legacy_layout_config_is_mapped_to_rapyd_layout()
    {
        config(['layout.logo_sidebar' => '/img/legacy.png', 'rapyd.layout.logo_sidebar' => null]);
        (new \Zofe\Rapyd\Modules\Layout\LayoutModuleServiceProvider($this->app))->register();

        $this->assertSame('/img/legacy.png', config('rapyd.layout.logo_sidebar'));
    }

    public function test_a_visitor_can_switch_theme_when_the_switch_is_enabled()
    {
        config(['rapyd.theme_switch' => true]);
        $this->app->register(DemoThemeServiceProvider::class);
        $this->loginAsAdmin();

        // picker in the navbar, bundled look active
        $this->get(route('auth.users'))->assertOk()->assertDontSee('DEMO THEME')->assertSee('rapyd_theme=demo');

        // the choice is stored in the session and the query string dropped
        $this->get(route('auth.users', ['rapyd_theme' => 'demo']))->assertRedirect(route('auth.users'));
        $this->get(route('auth.users'))->assertOk()->assertSee('DEMO THEME');
        $this->assertStringContainsString('vendor/themes/demo/rapyd.css', Blade::render('@rapydStyles'));

        // back to the bundled look
        $this->get(route('auth.users', ['rapyd_theme' => 'default']))->assertRedirect(route('auth.users'));
        $this->get(route('auth.users'))->assertOk()->assertDontSee('DEMO THEME');
        $this->assertStringContainsString('vendor/rapyd/rapyd.css', Blade::render('@rapydStyles'));

        // unknown names are ignored
        $this->get(route('auth.users', ['rapyd_theme' => 'nope']))->assertOk()->assertDontSee('DEMO THEME');
    }

    public function test_the_picker_can_be_hidden_while_links_still_switch()
    {
        config(['rapyd.theme_switch' => true, 'rapyd.theme_picker' => false]);
        $this->app->register(DemoThemeServiceProvider::class);
        $this->loginAsAdmin();

        $this->get(route('auth.users'))->assertOk()->assertDontSee('rapyd_theme=');
        $this->get(route('auth.users', ['rapyd_theme' => 'demo']))->assertRedirect(route('auth.users'));
        $this->get(route('auth.users'))->assertSee('DEMO THEME');
    }

    public function test_the_theme_switch_is_off_by_default()
    {
        $this->app->register(DemoThemeServiceProvider::class);
        $this->loginAsAdmin();

        $this->get(route('auth.users', ['rapyd_theme' => 'demo']))->assertOk()->assertDontSee('DEMO THEME')->assertDontSee('rapyd_theme=');
        $this->get(route('auth.users'))->assertDontSee('DEMO THEME');
    }

    public function test_layout_keys_missing_from_a_published_config_get_the_package_defaults()
    {
        config(['rapyd.layout' => ['brand' => 'Mine']]);
        (new \Zofe\Rapyd\RapydServiceProvider($this->app))->register();

        $this->assertSame('Mine', config('rapyd.layout.brand'));
        $this->assertTrue(config('rapyd.layout.auth_links'));
        $this->assertArrayHasKey('primary', config('rapyd.layout.palette'));
    }

    public function test_frontend_navbar_shows_the_theme_toggle_to_guests_and_can_hide_the_auth_links()
    {
        $this->seed(AuthSeeder::class);
        $html = view('layout::frontend')->render();
        $this->assertStringContainsString('Toggle dark mode', $html);
        $this->assertStringContainsString(route('login'), $html);

        config(['rapyd.layout.auth_links' => false]);
        $html = view('layout::frontend')->render();
        $this->assertStringContainsString('Toggle dark mode', $html);
        $this->assertStringNotContainsString(route('login'), $html);
    }
}
