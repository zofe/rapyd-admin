<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup;

use Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup;

/** No service configured: the form shows the manual fields only. */
class NullLookup implements AddressLookup
{
    public function name(): string
    {
        return 'none';
    }

    public function search(string $query, ?string $session = null): array
    {
        return [];
    }

    public function resolve(AddressCandidate $candidate, ?string $session = null): AddressCandidate
    {
        return $candidate;
    }
}
