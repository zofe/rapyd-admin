<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Zofe\Rapyd\Tests\TestCase;

class AppSwitcherTest extends TestCase
{
    protected function sidebar(): string
    {
        return Blade::render('@include("layout::includes.admin_sidebar")');
    }

    public function test_without_apps_the_sidebar_is_the_one_of_always()
    {
        config(['rapyd.layout.apps' => [], 'rapyd.layout.brand' => 'Acme']);
        $html = $this->sidebar();

        $this->assertStringContainsString('sidebar-brand', $html);
        $this->assertStringContainsString('Acme', $html);
        $this->assertStringNotContainsString('sidebar-apps', $html);
        $this->assertStringNotContainsString('Switch application', $html);
    }

    public function test_a_single_app_is_not_a_choice()
    {
        config(['rapyd.layout.apps' => [['name' => 'box-console', 'url' => null]]]);

        $this->assertSame([], rapyd_apps());
        $this->assertStringNotContainsString('sidebar-apps', $this->sidebar());
    }

    public function test_three_apps_give_a_toggle_the_current_one_and_the_links()
    {
        config([
            'rapyd.layout.apps' => [
                ['name' => 'box-console', 'url' => null, 'icon' => 'server'],
                ['name' => 'Uania Desk', 'url' => 'https://desk.uania.cloud', 'icon' => 'building'],
                ['name' => 'NOC board', 'url' => 'https://nocboard.tool.uania.cloud', 'icon' => 'chart-line'],
            ],
        ]);
        $html = $this->sidebar();

        $this->assertStringContainsString('aria-label="Switch application"', $html);
        $this->assertStringContainsString('aria-haspopup="true"', $html);
        $this->assertStringContainsString('data-bs-toggle="dropdown"', $html);

        // the current one is marked and is not a link
        $this->assertStringContainsString('aria-current="true"', $html);
        $this->assertMatchesRegularExpression('/<span class="dropdown-item active"[^>]*>\s*<i class="fas fa-check[^>]*><\/i>box-console/', $html);

        // the others are links that open in the same tab
        $this->assertStringContainsString('href="https://desk.uania.cloud"', $html);
        $this->assertStringContainsString('href="https://nocboard.tool.uania.cloud"', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
        $this->assertStringContainsString('fa-building', $html);

        // the brand still links to the home
        $this->assertStringContainsString('sidebar-brand', $html);
    }

    public function test_the_current_app_is_the_one_without_a_url()
    {
        config(['rapyd.layout.apps' => [
            ['name' => 'Desk', 'url' => 'https://desk.uania.cloud'],
            ['name' => 'Console', 'url' => null],
        ]]);

        $apps = rapyd_apps();
        $this->assertFalse($apps[0]['current']);
        $this->assertTrue($apps[1]['current']);
    }

    public function test_failing_that_it_is_the_one_matching_app_url()
    {
        config([
            'app.url' => 'https://nocboard.tool.uania.cloud',
            'rapyd.layout.apps' => [
                ['name' => 'Desk', 'url' => 'https://desk.uania.cloud'],
                ['name' => 'NOC board', 'url' => 'https://nocboard.tool.uania.cloud/'],
            ],
        ]);

        $apps = rapyd_apps();
        $this->assertFalse($apps[0]['current']);
        $this->assertTrue($apps[1]['current']);
    }

    public function test_with_nothing_matching_the_first_entry_is_the_current_one()
    {
        config([
            'app.url' => 'https://somewhere.else',
            'rapyd.layout.apps' => [
                ['name' => 'Desk', 'url' => 'https://desk.uania.cloud'],
                ['name' => 'NOC board', 'url' => 'https://nocboard.tool.uania.cloud'],
            ],
        ]);

        $this->assertTrue(rapyd_apps()[0]['current']);
    }

    public function test_entries_without_a_name_are_dropped()
    {
        config(['rapyd.layout.apps' => [
            ['name' => 'Console', 'url' => null],
            ['url' => 'https://desk.uania.cloud'],
            ['name' => '   '],
            ['name' => 'Desk', 'url' => 'https://desk.uania.cloud'],
        ]]);

        $apps = rapyd_apps();
        $this->assertCount(2, $apps);
        $this->assertSame(['Console', 'Desk'], array_column($apps, 'name'));
    }
}
