<?php

namespace App\Modules\Addresses;


use Illuminate\Support\ServiceProvider;

class AddressesServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/addresses.php', 'addresses');
    }
}
