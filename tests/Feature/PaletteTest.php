<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Zofe\Rapyd\Tests\TestCase;
use Zofe\Rapyd\Themes\Palette;

class PaletteTest extends TestCase
{
    public function test_no_palette_emits_nothing()
    {
        config(['rapyd.layout.palette' => ['primary' => null, 'sidebar_bg' => '']]);

        $this->assertSame('', Palette::css());
        $this->assertStringNotContainsString('rapyd-palette', Blade::render('@rapydStyles'));
    }

    public function test_primary_sets_bootstrap_variables_for_light_and_dark()
    {
        $css = Palette::css(['primary' => '#6a1c9a']);

        $this->assertStringContainsString('--bs-primary: #6a1c9a', $css);
        $this->assertStringContainsString('--bs-primary-rgb: 106, 28, 154', $css);
        $this->assertStringContainsString('--bs-link-color: #6a1c9a', $css);
        $this->assertStringContainsString('html.dark { --bs-primary: color-mix(in srgb, #6a1c9a 70%, white) !important', $css);
    }

    public function test_layout_tokens_and_sidebar_text()
    {
        $css = Palette::css(['sidebar_bg' => '#1e293b', 'sidebar_text' => 'rgb(248, 250, 252)', 'topbar_bg' => '#fff', 'content_bg' => '#f1f5f9']);

        $this->assertStringContainsString('--rpd-sidebar-bg: #1e293b', $css);
        $this->assertStringContainsString('--rpd-topbar-bg: #fff', $css);
        $this->assertStringContainsString('--rpd-content-bg: #f1f5f9', $css);
        $this->assertStringContainsString('.sidebar .nav-link', $css);
        $this->assertStringContainsString('color: rgb(248, 250, 252);', $css);
        $this->assertStringNotContainsString('--bs-primary', $css);
    }

    public function test_invalid_values_and_unknown_keys_are_dropped()
    {
        $this->assertSame('', Palette::css(['primary' => '#abc</style><script>', 'evil' => '#000']));
        $this->assertSame('', Palette::css(['sidebar_bg' => 'url(x)']));

        $css = Palette::css(['primary' => '#abc', 'sidebar_bg' => 'rebeccapurple', 'topbar_bg' => 'hsl(210 40% 98%)']);
        $this->assertStringContainsString('--bs-primary-rgb: 170, 187, 204', $css);
        $this->assertStringContainsString('--rpd-sidebar-bg: rebeccapurple', $css);
        $this->assertStringContainsString('--rpd-topbar-bg: hsl(210 40% 98%)', $css);
    }

    public function test_directive_emits_the_style_block_after_the_stylesheet()
    {
        config(['rapyd.layout.palette' => ['primary' => '#6a1c9a']]);
        $html = Blade::render('@rapydStyles');

        $this->assertStringContainsString('vendor/rapyd/rapyd.css', $html);
        $this->assertStringContainsString('<style id="rapyd-palette">', $html);
        $this->assertGreaterThan(strpos($html, 'rapyd.css'), strpos($html, 'rapyd-palette'));
    }
}
