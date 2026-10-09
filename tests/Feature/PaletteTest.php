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

    public function test_light_values_do_not_leak_into_dark_mode()
    {
        $css = Palette::css(['content_bg' => '#f8f9fc']);

        $this->assertStringContainsString('html:not(.dark) { --rpd-content-bg: #f8f9fc; }', $css);
        $this->assertStringNotContainsString('html.dark', $css);
    }

    public function test_dark_block_drives_the_same_tokens()
    {
        $css = Palette::css([
            'sidebar_bg' => '#4e73df',
            'dark' => ['sidebar_bg' => '#16244a', 'content_bg' => '#0f1424'],
        ]);

        $this->assertStringContainsString('html:not(.dark) { --rpd-sidebar-bg: #4e73df', $css);
        $this->assertStringContainsString('html.dark { --rpd-sidebar-bg: #16244a', $css);
        $this->assertStringContainsString('--rpd-content-bg: #0f1424', $css);
    }

    public function test_gradients_are_allowed_as_backgrounds_only()
    {
        $gradient = 'linear-gradient(180deg, #4e73df 10%, #224abe 100%)';
        $css = Palette::css(['sidebar_bg' => $gradient, 'primary' => $gradient]);

        $this->assertStringContainsString("--rpd-sidebar-bg: {$gradient}", $css);
        // color-mix() needs a colour: a gradient sidebar gets translucent shades instead
        $this->assertStringContainsString('--rpd-sidebar-inner-bg: rgba(0, 0, 0, .14)', $css);
        $this->assertStringNotContainsString('--bs-primary', $css);
        $this->assertSame('', Palette::css(['sidebar_bg' => 'linear-gradient(url(http://x))']));
    }

    public function test_border_colour_reaches_tables_cards_and_dropdowns()
    {
        $css = Palette::css(['border_color' => '#b7c0d4']);

        $this->assertStringContainsString('--rpd-border-color: #b7c0d4', $css);
        $this->assertStringContainsString('--bs-border-color: #b7c0d4', $css);
        $this->assertStringContainsString('--bs-card-border-color: #b7c0d4', $css);
        $this->assertStringContainsString('--bs-dropdown-border-color: #b7c0d4', $css);
    }

    public function test_sidebar_border_is_derived_from_the_sidebar_background()
    {
        $derived = Palette::css(['sidebar_bg' => '#4e73df']);
        $this->assertStringContainsString('--rpd-sidebar-border: rgba(0, 0, 0, .25)', $derived);
        $this->assertStringContainsString('.sidebar hr.sidebar-divider { border-top-color: rgba(0, 0, 0, .25); opacity: 1; }', $derived);

        // with a light sidebar text the hairline follows it, instead of being a black line on colour
        $fromText = Palette::css(['sidebar_bg' => '#4e73df', 'sidebar_text' => '#ffffff']);
        $this->assertStringContainsString('--rpd-sidebar-border: color-mix(in srgb, #ffffff 20%, transparent)', $fromText);

        $explicit = Palette::css(['sidebar_bg' => '#4e73df', 'sidebar_border' => 'rgba(255, 255, 255, .16)']);
        $this->assertStringContainsString('--rpd-sidebar-border: rgba(255, 255, 255, .16)', $explicit);

        // no sidebar at all: nothing to separate
        $this->assertStringNotContainsString('--rpd-sidebar-border', Palette::css(['content_bg' => '#fff']));
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
