<?php

namespace Zofe\Rapyd\Modules\Layout;

use Illuminate\Routing\Router;
use Zofe\Rapyd\Modules\Layout\Http\Middleware\LayoutByConfig;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class LayoutModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Layout';

    public function register(): void
    {
    }

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        $this->loadViewsFrom($this->srcPath('Views'), 'layout');

        $this->app->make(Router::class)->aliasMiddleware('layout.config', LayoutByConfig::class);
    }
}
