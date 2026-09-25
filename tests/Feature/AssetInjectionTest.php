<?php

namespace Zofe\Rapyd\Tests\Feature;

use Zofe\Rapyd\Tests\TestCase;

/**
 * The assets of Rapyd are injected in the HTML responses of the application, but not in the
 * pages of a package that ships its own complete UI (config rapyd.skip_asset_injection):
 * adding the Bootstrap of Rapyd to theirs breaks their layout (Horizon, Telescope, Pulse…).
 */
class AssetInjectionTest extends TestCase
{
    /** A page with a complete layout, as a package would serve it. */
    protected const PAGE = '<html><head><title>Dashboard</title></head><body><div class="row"><div class="col-2">menu</div></div></body></html>';

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);

        foreach (['own-page', 'horizon', 'horizon/dashboard/jobs', 'telescope/requests', 'not-horizon'] as $path) {
            $router->middleware('web')->get($path, fn () => response(self::PAGE)->header('content-type', 'text/html'));
        }
    }

    public function test_a_page_of_the_application_receives_the_assets()
    {
        $html = $this->get('/own-page')->assertOk()->getContent();

        $this->assertStringContainsString('/rapyd.js', $html);
        $this->assertStringContainsString('/rapyd.css', $html);
    }

    public function test_the_pages_of_a_package_with_its_own_ui_are_left_alone()
    {
        foreach (['/horizon', '/horizon/dashboard/jobs', '/telescope/requests'] as $url) {
            $this->assertSame(self::PAGE, $this->get($url)->assertOk()->getContent(), "{$url}: untouched");
        }

        // the prefix must match a whole segment: /not-horizon is a page of the application
        $this->assertStringContainsString('/rapyd.js', $this->get('/not-horizon')->getContent());
    }

    public function test_the_list_comes_from_the_config_and_has_a_default_when_the_application_published_an_older_file()
    {
        $this->assertContains('horizon/*', config('rapyd.skip_asset_injection'), 'default from the package');

        config(['rapyd.skip_asset_injection' => ['own-page']]);
        $this->assertSame(self::PAGE, $this->get('/own-page')->getContent(), 'excluded now');
        $this->assertStringContainsString('/rapyd.js', $this->get('/horizon')->getContent(), 'no longer excluded');

        config(['rapyd.skip_asset_injection' => []]);
        $this->assertStringContainsString('/rapyd.js', $this->get('/horizon')->getContent(), 'an empty list excludes nothing');
    }
}
