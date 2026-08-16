<?php

namespace Zofe\Rapyd\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
