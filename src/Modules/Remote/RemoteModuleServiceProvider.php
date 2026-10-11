<?php

namespace Zofe\Rapyd\Modules\Remote;

use Illuminate\Support\Facades\Route;
use Zofe\Rapyd\Modules\Remote\Http\AuthenticateRemoteCaller;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class RemoteModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Remote';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/remote.php', 'rapyd.remote');
    }

    public function boot(): void
    {
        if (! config('rapyd.remote.enabled')) {
            return;
        }

        $this->loadMigrationsFrom($this->srcPath('Database/Migrations'));

        // provider endpoints: api middleware, no language prefix
        Route::middleware(['api', AuthenticateRemoteCaller::class])
            ->prefix('_remote')
            ->group($this->srcPath('routes.php'));

        if (! $this->isEjected()) {
            $this->bootAppModule('remote'); // components (remote::components.*) and the lookup route
        }
    }
}
