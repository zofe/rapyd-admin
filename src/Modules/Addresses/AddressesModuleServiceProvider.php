<?php

namespace Zofe\Rapyd\Modules\Addresses;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class AddressesModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Addresses';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/addresses.php', 'rapyd.addresses');

        $this->app->bind(Lookup\Contracts\AddressLookup::class, function ($app) {
            $driver = config('rapyd.addresses.lookup', 'none');

            return match ($driver) {
                'none', null, '' => new Lookup\NullLookup(),
                'google'         => new Lookup\GoogleLookup(),
                default          => $app->make($driver),
            };
        });

        // Legacy names from the separate packages, still used by other modules (e.g. shop-module).
        if (! class_exists('App\Modules\Addresses\Models\Address', false)) {
            class_alias(\Zofe\Rapyd\Modules\Addresses\Models\Address::class, 'App\Modules\Addresses\Models\Address');
        }
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
