<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup\Contracts;

use Zofe\Rapyd\Modules\Addresses\Lookup\AddressCandidate;

/**
 * Turns a few typed words into address candidates. Implement it for any
 * geocoding service and name the class in config('rapyd.addresses.lookup').
 *
 * $session: an opaque token the form generates each time it opens; services
 * billing by session (Google Places) get the same one for every call of a search.
 */
interface AddressLookup
{
    /** A short name stored on the address as `verified_by`. */
    public function name(): string;

    /** @return AddressCandidate[] best matches first; [] when nothing matches or the service is down */
    public function search(string $query, ?string $session = null): array;

    /** The fields of a chosen candidate, when search() only returned labels (a second call for some services). */
    public function resolve(AddressCandidate $candidate, ?string $session = null): AddressCandidate;
}
