<?php

namespace Zofe\Rapyd\Modules\Companies;

use Zofe\Rapyd\Modules\Companies\Authorizations\CompanyAuth;
use Zofe\Rapyd\Modules\Companies\Authorizations\CompanyOwnerAuth;
use Zofe\Rapyd\Modules\Companies\Limits\CompanyLimit;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class CompaniesModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Companies';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/companies.php', 'rapyd.companies');

        $moduleConfig = dirname(__DIR__, 3) . '/app/Modules/Companies/config.php';
        if (file_exists($moduleConfig)) {
            $this->mergeConfigFrom($moduleConfig, 'companies');
        }
    }

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        if (! config('rapyd.companies.enabled', true)) {
            return;
        }

        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'company' => \Zofe\Rapyd\Modules\Companies\Models\Company::class,
        ], true);

        $this->loadMigrationsFrom($this->srcPath('Database/Migrations'));

        // Views: src/ for auth layouts, app/ for CRUD views
        $this->loadViewsFrom($this->srcPath('Views'), 'companies');
        $appViews = dirname(__DIR__, 3) . '/app/Modules/Companies/Views';
        if (is_dir($appViews)) {
            $this->loadViewsFrom($appViews, 'companies');
        }

        // Routes
        $routesFile = dirname(__DIR__, 3) . '/app/Modules/Companies/routes.php';
        if (file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }

        $this->registerLivewireNamespace(dirname(__DIR__, 3) . '/app/Modules/Companies/Livewire');

        $this->registerLimit(CompanyLimit::class);
        $this->registerAuthorization(CompanyAuth::class);
        $this->registerAuthorization(CompanyOwnerAuth::class);
    }
}
