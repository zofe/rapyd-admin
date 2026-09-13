<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup\Contracts;

use Zofe\Rapyd\Modules\Addresses\Lookup\AddressCandidate;

/**
 * Turns a few typed words into address candidates. Implement it for any
 * geocoding service and name the class in config('rapyd.addresses.lookup').
 */
interface AddressLookup
{
    /** A short name stored on the address as `verified_by`. */
    public function name(): string;

    /** @return AddressCandidate[] best matches first; [] when nothing matches or the service is down */
    public function search(string $query): array;
}
