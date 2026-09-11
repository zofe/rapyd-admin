<?php

namespace Zofe\Rapyd\Modules\Addresses;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class AddressesModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Addresses';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/addresses.php', 'rapyd.addresses');
    }

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        if (! config('rapyd.addresses.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom($this->srcPath('Database/Migrations'));
        $this->bootAppModule('addresses');
    }
}
