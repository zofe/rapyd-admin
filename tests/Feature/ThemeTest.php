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
}
