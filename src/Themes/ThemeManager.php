<?php

namespace Zofe\Rapyd\Themes;

use Illuminate\Contracts\View\Factory;

/**
 * Switches the active theme at runtime (per visitor, see ThemeBySession).
 * RapydThemeServiceProvider activates the configured theme at boot; this class
 * moves the theme view folders in and out of the `layout` / `rpd` namespaces
 * and points @rapydStyles / @rapydScripts at the right published assets.
 */
class ThemeManager
{
    public const SESSION_KEY = 'rapyd_theme';

    public const QUERY = 'rapyd_theme';

    /** Name of the bundled look (no theme package). */
    public const BUNDLED = 'default';

    public function __construct(protected Factory $view)
    {
    }

    /** Registered themes: name => root path (from every RapydThemeServiceProvider). */
    public function available(): array
    {
        return config('rapyd.themes', []);
    }

    /** Names offered by the picker: the bundled look first, then the registered themes. */
    public function names(): array
    {
        return array_merge([self::BUNDLED], array_keys($this->available()));
    }

    public function has(?string $name): bool
    {
        return $name === self::BUNDLED || ($name !== null && isset($this->available()[$name]));
    }

    public function active(): string
    {
        return config('rapyd.theme') ?: self::BUNDLED;
    }

    /** Make $name the active theme for the rest of the request: views, rpd overrides and assets. */
    public function activate(string $name): void
    {
        if (! $this->has($name) || $name === $this->active()) {
            return;
        }

        foreach ($this->available() as $path) {
            $this->removeHint('layout', $path . '/resources/views');
            $this->removeHint('rpd', $path . '/resources/views/rpd');
        }
        // Paths already resolved in this process (tests, Octane) would keep pointing at the old theme.
        $this->view->getFinder()->flush();

        if ($name === self::BUNDLED) {
            config(['rapyd.theme' => null, 'rapyd.theme_assets' => 'vendor/rapyd']);

            return;
        }

        $path = $this->available()[$name];
        $this->view->prependNamespace('layout', $path . '/resources/views');
        if (is_dir($path . '/resources/views/rpd')) {
            $this->view->prependNamespace('rpd', $path . '/resources/views/rpd');
        }
        config(['rapyd.theme' => $name, 'rapyd.theme_assets' => 'vendor/themes/' . $name]);
    }

    protected function removeHint(string $namespace, string $path): void
    {
        $hints = $this->view->getFinder()->getHints()[$namespace] ?? [];
        $left = array_values(array_filter($hints, fn ($hint) => $hint !== $path));
        if ($left !== $hints) {
            $this->view->replaceNamespace($namespace, $left);
        }
    }
}
