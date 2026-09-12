<?php

namespace Zofe\Rapyd\Themes;

/**
 * Runtime palette: turns config('rapyd.layout.palette') into a <style> block
 * that overrides the theme's CSS variables. No rebuild needed: the components
 * read --bs-primary & co. (see resources/sass/_tokens.scss).
 *
 * Keys (all optional): primary, sidebar_bg, sidebar_text, topbar_bg, content_bg.
 * Values: any CSS colour (#hex, rgb(), hsl()…).
 */
class Palette
{
    public const KEYS = ['primary', 'sidebar_bg', 'sidebar_text', 'topbar_bg', 'content_bg'];

    public static function css(?array $palette = null): string
    {
        $palette = array_filter(array_intersect_key(
            $palette ?? config('rapyd.layout.palette', []),
            array_flip(self::KEYS)
        ), fn ($v) => is_string($v) && trim($v) !== '');

        if (! $palette) {
            return '';
        }

        $palette = array_filter(array_map([self::class, 'validate'], $palette));
        if (! $palette) {
            return '';
        }
        $root = [];
        $dark = [];
        $rules = [];

        if ($p = $palette['primary'] ?? null) {
            $root[] = "--bs-primary: {$p}";
            if ($rgb = self::rgb($p)) {
                $root[] = "--bs-primary-rgb: {$rgb}";
            }
            $root[] = "--bs-link-color: {$p}";
            $root[] = "--bs-link-hover-color: color-mix(in srgb, {$p} 80%, black)";
            $root[] = "--bs-link-color-rgb: " . ($rgb ?: 'var(--bs-primary-rgb)');
            // Dark mode keeps its own backgrounds but follows the accent, lightened for contrast.
            $dark[] = "--bs-primary: color-mix(in srgb, {$p} 70%, white) !important";
            $dark[] = "--bs-link-color: color-mix(in srgb, {$p} 60%, white)";
        }
        if ($v = $palette['sidebar_bg'] ?? null) {
            $root[] = "--rpd-sidebar-bg: {$v}";
            // Open sub-menus and the active item: a bit darker than the sidebar itself.
            $root[] = "--rpd-sidebar-inner-bg: color-mix(in srgb, {$v} 92%, black)";
            $root[] = "--rpd-sidebar-active-bg: color-mix(in srgb, {$v} 80%, black)";
        }
        if ($v = $palette['topbar_bg'] ?? null) {
            $root[] = "--rpd-topbar-bg: {$v}";
        }
        if ($v = $palette['content_bg'] ?? null) {
            $root[] = "--rpd-content-bg: {$v}";
        }
        if ($v = $palette['sidebar_text'] ?? null) {
            $rules[] = ".sidebar, .sidebar .nav-link, .sidebar .sidebar-brand, .sidebar .collapse-inner a, .sidebar .sidebar-heading { color: {$v}; }";
        }

        $css = $root ? ':root { ' . implode('; ', $root) . '; }' : '';
        $css .= $dark ? "\nhtml.dark { " . implode('; ', $dark) . '; }' : '';
        $css .= $rules ? "\n" . implode("\n", $rules) : '';

        return $css;
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

    /** A CSS colour (#hex, rgb()/hsl() functions, named colour) or null: anything else is dropped. */
    public static function validate(string $value): ?string
    {
        $value = trim($value);
        $ok = preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)
            || preg_match('/^(rgb|rgba|hsl|hsla)\([0-9.,%\s\/deg]+\)$/i', $value)
            || preg_match('/^[a-z]{3,20}$/i', $value);

        return $ok ? $value : null;
    }
}
