<?php

namespace Zofe\Rapyd\Modules\Companies;

use Zofe\Rapyd\Modules\Companies\Authorizations\CompanyAuth;
use Zofe\Rapyd\Modules\Companies\Limits\CompanyLimit;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class CompaniesModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Companies';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/companies.php', 'rapyd.companies');

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

        $this->loadViewsFrom($this->srcPath('Views'), 'companies');
        $this->bootAppModule('companies');

        $this->registerLimit(CompanyLimit::class);
        $this->registerAuthorization(CompanyAuth::class);
    }
}
