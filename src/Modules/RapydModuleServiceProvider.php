<?php

namespace Zofe\Rapyd\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

abstract class RapydModuleServiceProvider extends ServiceProvider
{
    /**
     * The module name, e.g. "Auth", "Companies".
     * Must be overridden in every bundled module provider.
     */
    protected string $moduleName = '';

    /**
     * True when the user has ejected this module to app/Modules/{name}.
     * In that case ModuleServiceProvider handles loading — skip here.
     */
    protected function isEjected(): bool
    {
        return (new Filesystem)->isDirectory(app_path("Modules/{$this->moduleName}"));
    }

    /**
     * Absolute path to the bundled module source directory.
     */
    protected function srcPath(string $relative = ''): string
    {
        $base = dirname(__DIR__, 1) . "/Modules/{$this->moduleName}";

        return $relative ? $base . DIRECTORY_SEPARATOR . ltrim($relative, '/\\') : $base;
    }

    /**
     * Absolute path inside the package's app/Modules/{name} directory
     * (admin UI, views, translations, routes — the part that can be ejected).
     */
    protected function appModulePath(string $relative = ''): string
    {
        $base = dirname(__DIR__, 2) . "/app/Modules/{$this->moduleName}";

        return $relative ? $base . DIRECTORY_SEPARATOR . ltrim($relative, '/\\') : $base;
    }

    /**
     * Load everything a non-ejected module ships in app/Modules/{name}:
     * views, translations, routes and the "{namespace}::" Livewire components.
     */
    protected function bootAppModule(string $namespace): void
    {
        if (is_dir($this->appModulePath('Views'))) {
            $this->loadViewsFrom($this->appModulePath('Views'), $namespace);
        }
        if (is_dir($this->appModulePath('Lang'))) {
            $this->loadTranslationsFrom($this->appModulePath('Lang'), $namespace);
        }
        if (file_exists($this->appModulePath('routes.php'))) {
            $this->loadRoutesFrom($this->appModulePath('routes.php'));
        }
        if (is_dir($this->appModulePath('Livewire'))) {
            $this->registerLivewireNamespace($this->appModulePath('Livewire'));
        }
    }

    /**
     * Register the module's Livewire components under "{module}::" and add the
     * module to config('rapyd.modules'), mirroring what ModuleServiceProvider
     * does for modules discovered in app/Modules.
     */
    protected function registerLivewireNamespace(string $componentsDir): void
    {
        $module = Str::lower($this->moduleName);

        Livewire::addNamespace($module, null, "App\\Modules\\{$this->moduleName}\\Livewire", $componentsDir);

        $modules = config('rapyd.modules', []);
        if (! in_array($module, $modules)) {
            $modules[] = $module;
            config(['rapyd.modules' => $modules]);
        }
    }

    /**
     * Register a Limit class (FQCN) into config('auth.limits').
     */
    protected function registerLimit(string $fqcn): void
    {
        $limits = config('auth.limits', []);
        if (! in_array($fqcn, $limits)) {
            $limits[] = $fqcn;
        }
        config(['auth.limits' => $limits]);
    }

    /**
     * Register an Authorization class (FQCN) into config('auth.authorizations').
     */
    protected function registerAuthorization(string $fqcn): void
    {
        $checks = config('auth.authorizations', []);
        if (! in_array($fqcn, $checks)) {
            $checks[] = $fqcn;
        }
        config(['auth.authorizations' => $checks]);
    }
}
