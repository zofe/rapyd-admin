<?php

namespace Zofe\Rapyd\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;

abstract class RapydModuleServiceProvider extends ServiceProvider
{
    use MergesPermissions;

    /**
     * The module name, e.g. "Auth", "Companies".
     * Must be overridden in every module provider.
     */
    protected string $moduleName = '';

    /**
     * Root of the module when it is a package of its own (e.g. zofe/demo-module):
     * the folder holding Livewire/, Views/, routes.php, config.php…
     * Set it to __DIR__ in the package provider. Null = bundled module, whose
     * ejectable part lives in the package's app/Modules/{name}.
     */
    protected ?string $modulePath = null;

    /**
     * PSR-4 namespace of the module's Livewire components.
     * Defaults to App\Modules\{Name}\Livewire, the convention every module follows.
     */
    protected ?string $livewireNamespace = null;

    /**
     * External modules: merge config.php as config('{name}') so the menu and
     * layout entries are known before ModuleServiceProvider builds the menus.
     * Bundled modules override register() and merge what they need themselves.
     */
    public function register(): void
    {
        $this->mergeModuleConfig();
    }

    protected function mergeModuleConfig(): void
    {
        $config = $this->appModulePath('config.php');
        if ($this->modulePath && file_exists($config)) {
            $this->mergeConfigFrom($config, Str::lower($this->moduleName));
            $this->mergePermissions(config(Str::lower($this->moduleName)));
        }
    }

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
     * Absolute path of the module's UI part (views, translations, routes, Livewire):
     * $modulePath for an external package, app/Modules/{name} of this package for
     * a bundled module (the part that can be ejected).
     */
    protected function appModulePath(string $relative = ''): string
    {
        $base = $this->modulePath
            ? rtrim($this->modulePath, '/\\')
            : dirname(__DIR__, 2) . "/app/Modules/{$this->moduleName}";

        return $relative ? $base . DIRECTORY_SEPARATOR . ltrim($relative, '/\\') : $base;
    }

    /**
     * Load everything a non-ejected module ships: views, translations, routes,
     * the "{namespace}::" Livewire components and, for external modules, the
     * migrations in Database/Migrations.
     */
    protected function bootAppModule(string $namespace): void
    {
        if ($this->modulePath && is_dir($this->appModulePath('Database/Migrations'))) {
            $this->loadMigrationsFrom($this->appModulePath('Database/Migrations'));
        }
        // Same view roots as ModuleServiceProvider gives an app module: the
        // Blade files next to the Livewire classes resolve as "{namespace}::" too.
        foreach (['Views', 'Livewire', 'Components'] as $dir) {
            if (is_dir($this->appModulePath($dir))) {
                $this->loadViewsFrom($this->appModulePath($dir), $namespace);
            }
        }
        if (is_dir($this->appModulePath('Lang'))) {
            $this->loadTranslationsFrom($this->appModulePath('Lang'), $namespace);
        }
        if (file_exists($this->appModulePath('routes.php'))) {
            $this->loadRoutesFrom($this->appModulePath('routes.php'));
        }
        if ($this->modulePath && file_exists($this->appModulePath('workflow.php'))) {
            $this->registerWorkflowDefinitions($this->appModulePath('workflow.php'));
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

        $namespace = $this->livewireNamespace ?? "App\\Modules\\{$this->moduleName}\\Livewire";

        Livewire::addNamespace($module, null, $namespace, $componentsDir);

        $modules = config('rapyd.modules', []);
        if (! in_array($module, $modules)) {
            $modules[] = $module;
            config(['rapyd.modules' => $modules]);
        }
    }

    /**
     * Add the state machines of a workflow.php to config('workflow'), which
     * laravel-workflow reads lazily; a name already defined wins.
     */
    protected function registerWorkflowDefinitions(string $file): void
    {
        $definitions = config('workflow', []);
        $defs = require $file;
        foreach (is_array($defs) ? $defs : [] as $name => $definition) {
            if (is_array($definition) && ! isset($definitions[$name])) {
                $definitions[$name] = $definition;
            }
        }
        config(['workflow' => $definitions]);
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
