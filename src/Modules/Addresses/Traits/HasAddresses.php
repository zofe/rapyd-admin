<?php

namespace Zofe\Rapyd\Modules\Addresses\Traits;

use Zofe\Rapyd\Modules\Addresses\Models\Address;

trait HasAddresses
{
    public function addresses()
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function hasAnyAddresses(): bool
    {
        if ($this->addresses()->exists()) {
            return true;
        }

        return method_exists($this, 'company') && $this->company?->addresses()->exists();
    }
}
