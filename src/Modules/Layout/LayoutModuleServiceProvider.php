<?php

namespace Zofe\Rapyd\Modules\Layout;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Zofe\Rapyd\Modules\Layout\Http\Middleware\LayoutByConfig;
use Zofe\Rapyd\Modules\Layout\Http\Middleware\ThemeBySession;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class LayoutModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Layout';

    public function register(): void
    {
        // Branding used to live in config('layout.*'): keep reading it.
        foreach (['brand', 'logo_sidebar', 'logo_login', 'favicon', 'custom_css'] as $key) {
            if (config("rapyd.layout.{$key}") === null && config("layout.{$key}") !== null) {
                config(["rapyd.layout.{$key}" => config("layout.{$key}")]);
            }
        }
    }

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        $this->loadViewsFrom($this->srcPath('Views'), 'layout');

        $this->app->make(Router::class)->aliasMiddleware('layout.config', LayoutByConfig::class);

        // On the Kernel, not the Router: the Kernel syncs its groups to the Router on every request.
        $this->app->make(Kernel::class)->appendMiddlewareToGroup('web', ThemeBySession::class);
    }
}
