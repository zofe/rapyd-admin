<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup\Contracts;

use Zofe\Rapyd\Modules\Addresses\Lookup\AddressCandidate;
use Zofe\Rapyd\Modules\Addresses\Models\Address;

/** A service that says whether an address exists as typed (config rapyd.addresses.validate). */
interface AddressValidator
{
    /**
     * The normalised address with a confidence: verified (exists, building level),
     * partial (found something, some components unconfirmed) or unknown (not found).
     * Null when the service could not answer.
     */
    public function validate(Address $address): ?AddressCandidate;
}
