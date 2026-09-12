<?php

namespace Zofe\Rapyd\Themes;

use Illuminate\Support\ServiceProvider;

/**
 * Base class for a rapyd-admin theme (see docs/THEMES.md).
 *
 * A theme is a folder with resources/views/{app,admin,frontend,auth}.blade.php
 * (+ includes) and the compiled assets in public/. When it is the active theme
 * (config rapyd.theme === $name) its views are looked up BEFORE the bundled
 * ones on the `layout` namespace, and optionally before the `rpd` components,
 * and @rapydStyles / @rapydScripts point at its published assets.
 */
abstract class RapydThemeServiceProvider extends ServiceProvider
{
    /** Theme name: the value of config('rapyd.theme') that activates it. */
    protected string $name;

    /** Theme root: the folder holding resources/ and public/. */
    protected string $path;

    public function boot(): void
    {
        $themes = config('rapyd.themes', []);
        $themes[$this->name] = $this->path;
        config(['rapyd.themes' => $themes]);

        if ($this->app->runningInConsole() && is_dir($this->path . '/public')) {
            $this->publishes([
                $this->path . '/public' => public_path($this->assetsPath()),
            ], ['laravel-assets', 'rapyd-theme-' . $this->name]);
        }

        if (! $this->isActive()) {
            return;
        }

        $this->app['view']->prependNamespace('layout', $this->path . '/resources/views');

        // Overrides of the rpd:: views (x-rpd:: components live in rpd::components.*):
        // resources/views/rpd/components/nav-link.blade.php replaces x-rpd::nav-link.
        if (is_dir($this->path . '/resources/views/rpd')) {
            $this->app['view']->prependNamespace('rpd', $this->path . '/resources/views/rpd');
        }

        config(['rapyd.theme_assets' => $this->assetsPath()]);
    }

    public function isActive(): bool
    {
        return config('rapyd.theme') === $this->name;
    }

    /** Where the assets are published, relative to public/. */
    public function assetsPath(): string
    {
        return 'vendor/themes/' . $this->name;
    }
}
