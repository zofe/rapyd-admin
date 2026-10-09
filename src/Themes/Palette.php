<?php

namespace Zofe\Rapyd\Themes;

/**
 * Runtime palette: turns config('rapyd.layout.palette') into a <style> block
 * that overrides the theme's CSS variables. No rebuild needed: the components
 * read --bs-primary & co. (see resources/sass/_tokens.scss).
 *
 * Keys (all optional): primary, sidebar_bg, sidebar_text, sidebar_border, topbar_bg,
 * content_bg, border_color, plus a 'dark' sub-array with the same keys for dark mode.
 * Values: any CSS colour (#hex, rgb(), hsl()…); the background keys also take a gradient.
 */
class Palette
{
    public const KEYS = ['primary', 'sidebar_bg', 'sidebar_text', 'sidebar_border', 'topbar_bg', 'content_bg', 'border_color'];

    /** Keys that accept a gradient: the others are mixed with color-mix(), which needs a colour. */
    public const BACKGROUND_KEYS = ['sidebar_bg', 'topbar_bg', 'content_bg'];

    /** Light values must not leak into dark mode, where the theme has backgrounds of its own. */
    public const LIGHT_SCOPE = 'html:not(.dark)';

    public const DARK_SCOPE = 'html.dark';

    public static function css(?array $palette = null): string
    {
        $palette ??= (array) config('rapyd.layout.palette', []);

        $light = self::clean($palette);
        $dark = self::clean($palette['dark'] ?? []);

        // With no dark accent of its own, dark mode follows the light one, lightened for contrast.
        if (! isset($dark['primary']) && isset($light['primary'])) {
            $dark['primary'] = "color-mix(in srgb, {$light['primary']} 70%, white)";
        }

        $css = self::block($light, self::LIGHT_SCOPE, false);
        $darkCss = self::block($dark, self::DARK_SCOPE, true);

        return trim($css . ($css && $darkCss ? "\n" : '') . $darkCss);
    }

    /** Inline <style> for the layouts; empty string when no palette is configured. */
    public static function styleTag(): string
    {
        $css = self::css();

        return $css === '' ? '' : "<style id=\"rapyd-palette\">\n{$css}\n</style>\n";
    }

    /** "r, g, b" for a #hex colour, null for anything else. */
    public static function rgb(string $color): ?string
    {
        if (! preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $m)) {
            return null;
        }
        $hex = strlen($m[1]) === 3
            ? $m[1][0] . $m[1][0] . $m[1][1] . $m[1][1] . $m[1][2] . $m[1][2]
            : $m[1];

        return implode(', ', array_map('hexdec', str_split($hex, 2)));
    }

    /** A CSS colour (#hex, rgb()/hsl() functions, named colour) or a gradient; null for anything else. */
    public static function validate(string $value): ?string
    {
        $value = trim($value);

        // Nothing that could break out of the declaration, and no external fetch.
        if (preg_match('/[;{}<>@]|url\s*\(|\/\*/i', $value)) {
            return null;
        }

        $ok = preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)
            || preg_match('/^(rgb|rgba|hsl|hsla)\([0-9.,%\s\/deg]+\)$/i', $value)
            || preg_match('/^[a-z]{3,20}$/i', $value)
            || self::isGradient($value);

        return $ok ? $value : null;
    }

    public static function isGradient(string $value): bool
    {
        return (bool) preg_match('/^(repeating-)?(linear|radial|conic)-gradient\([#0-9a-z%,.\s()\/-]+\)$/i', trim($value));
    }

    /** Known keys, non-empty strings, validated; gradients only where a background is expected. */
    protected static function clean(array $palette): array
    {
        $values = self::values($palette);
        $clean = [];

        foreach ($values as $key => $value) {
            $value = self::validate($value);
            if ($value === null) {
                continue;
            }
            if (self::isGradient($value) && ! in_array($key, self::BACKGROUND_KEYS, true)) {
                continue;
            }
            $clean[$key] = $value;
        }

        return $clean;
    }

    /** Known keys with a non-empty string value, before validation. */
    protected static function values(array $palette): array
    {
        return array_filter(
            array_intersect_key($palette, array_flip(self::KEYS)),
            fn ($v) => is_string($v) && trim($v) !== ''
        );
    }

    /** One scoped rule set; the values are already validated. */
    protected static function block(array $values, string $scope, bool $dark): string
    {
        if (! $values) {
            return '';
        }

        $decl = [];
        $rules = [];

        if ($p = $values['primary'] ?? null) {
            // !important in dark mode: _dark.scss sets its own accent that way.
            $decl[] = "--bs-primary: {$p}" . ($dark ? ' !important' : '');
            if ($rgb = self::rgb($p)) {
                $decl[] = "--bs-primary-rgb: {$rgb}";
                $decl[] = "--bs-link-color-rgb: {$rgb}";
            }
            $decl[] = "--bs-link-color: {$p}";
            $decl[] = '--bs-link-hover-color: color-mix(in srgb, ' . $p . ' 80%, ' . ($dark ? 'white' : 'black') . ')';
        }

        if ($v = $values['sidebar_bg'] ?? null) {
            $decl[] = "--rpd-sidebar-bg: {$v}";
            // Open sub-menus and the active item: a bit darker than the sidebar itself.
            [$inner, $active] = self::isGradient($v)
                ? ['rgba(0, 0, 0, .14)', 'rgba(0, 0, 0, .26)']
                : ["color-mix(in srgb, {$v} 92%, black)", "color-mix(in srgb, {$v} 80%, black)"];
            $decl[] = "--rpd-sidebar-inner-bg: {$inner}";
            $decl[] = "--rpd-sidebar-active-bg: {$active}";
        }

        // The sidebar edge and its dividers. Derived from the sidebar text when there is one, so a
        // coloured sidebar gets a light hairline rather than a black one; a dark line otherwise.
        $border = $values['sidebar_border'] ?? null;
        if ($border === null && isset($values['sidebar_bg'])) {
            $border = isset($values['sidebar_text'])
                ? "color-mix(in srgb, {$values['sidebar_text']} 20%, transparent)"
                : 'rgba(0, 0, 0, .25)';
        }
        if ($border) {
            $decl[] = "--rpd-sidebar-border: {$border}";
            $rules[] = "{$scope} .sidebar hr.sidebar-divider { border-top-color: {$border}; opacity: 1; }";
        }

        if ($v = $values['topbar_bg'] ?? null) {
            $decl[] = "--rpd-topbar-bg: {$v}";
        }

        if ($v = $values['content_bg'] ?? null) {
            $decl[] = "--rpd-content-bg: {$v}";
        }

        if ($v = $values['border_color'] ?? null) {
            // --rpd-border-color reaches the dark-mode variables; --bs-* covers tables, cards and inputs in light.
            $decl[] = "--rpd-border-color: {$v}";
            $decl[] = "--bs-border-color: {$v}";
            $decl[] = "--bs-border-color-translucent: {$v}";
            // Cards and dropdowns carry their own Bootstrap variable, compiled from $border-color.
            $decl[] = "--bs-card-border-color: {$v}";
            $decl[] = "--bs-dropdown-border-color: {$v}";
        }

        if ($v = $values['sidebar_text'] ?? null) {
            // Scoped: it has to outrank .sidebar-light / .dark .sidebar .nav-item .nav-link.
            $targets = ['.sidebar', '.sidebar .nav-link', '.sidebar .nav-item .nav-link', '.sidebar .nav-item .nav-link i',
                '.sidebar .sidebar-brand', '.sidebar .collapse-inner a', '.sidebar .sidebar-heading'];
            $selector = implode(', ', array_map(fn ($t) => "{$scope} {$t}", $targets));
            $rules[] = "{$selector} { color: {$v}; }";
            // The collapse button is a plain grey circle: tie it to the sidebar text instead.
            $rules[] = "{$scope} .sidebar #sidebarToggle { background-color: color-mix(in srgb, {$v} 22%, transparent); }";
            $rules[] = "{$scope} .sidebar #sidebarToggle:hover { background-color: color-mix(in srgb, {$v} 34%, transparent); }";
            $rules[] = "{$scope} .sidebar #sidebarToggle::after { color: {$v}; }";
        }

        $css = $decl ? $scope . ' { ' . implode('; ', $decl) . '; }' : '';
        $css .= $rules ? ($css ? "\n" : '') . implode("\n", $rules) : '';

        return $css;
    }
}
