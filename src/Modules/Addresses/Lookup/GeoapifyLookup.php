<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup;

/**
 * Geoapify Address Autocomplete (https://apidocs.geoapify.com/docs/geocoding/address-autocomplete/):
 * 3,000 free credits a day, commercial use allowed with attribution. GEOAPIFY_KEY in .env.
 */
class GeoapifyLookup implements AddressLookup
{
    public const URL = 'https://api.geoapify.com/v1/geocode/autocomplete';

    public function name(): string
    {
        return 'geoapify';
    }

    public function search(string $query): array
    {
        $key = config('rapyd.addresses.geoapify_key');
        if (! $key || mb_strlen(trim($query)) < 3) {
            return [];
        }

        $params = [
            'text'   => trim($query),
            'apiKey' => $key,
            'format' => 'json',
            'limit'  => (int) config('rapyd.addresses.lookup_limit', 5),
            'lang'   => config('rapyd.addresses.lookup_lang') ?: substr(app()->getLocale(), 0, 2),
        ];
        if ($country = config('rapyd.addresses.lookup_country')) {
            $params['filter'] = 'countrycode:' . strtolower($country);
        }

        try {
            $response = Http::timeout(6)->get(self::URL, $params);
            if (! $response->ok()) {
                Log::warning('geoapify: ' . $response->status(), ['body' => mb_substr($response->body(), 0, 200)]);
                return [];
            }
        } catch (\Throwable $e) {
            Log::warning('geoapify: unreachable', ['error' => $e->getMessage()]);
            return [];
        }

        return array_values(array_map([$this, 'candidate'], $response->json('results', [])));
    }

    /** Geoapify's flat result (format=json) to an AddressCandidate. */
    public function candidate(array $r): AddressCandidate
    {
        $type = $r['result_type'] ?? '';
        $confidence = ($type === 'building' || $type === 'amenity' || ! empty($r['housenumber']))
            ? AddressCandidate::VERIFIED
            : AddressCandidate::PARTIAL;

        // state_code: Geoapify returns it for countries with subdivisions in their addresses (US, CA…), sometimes prefixed "US-CA"
        $state = $r['state_code'] ?? null;
        if ($state && str_contains($state, '-')) {
            $state = substr($state, strrpos($state, '-') + 1);
        }

        return new AddressCandidate(
            label: $r['formatted'] ?? trim(($r['address_line1'] ?? '') . ' ' . ($r['address_line2'] ?? '')),
            address: $r['street'] ?? $r['name'] ?? null,
            street_number: $r['housenumber'] ?? null,
            zipcode: $r['postcode'] ?? null,
            city: $r['city'] ?? $r['county'] ?? null,
            province: $r['county'] ?? null,
            region: $r['state'] ?? null,
            country: $r['country'] ?? null,
            country_code: $r['country_code'] ?? null,
            state_code: $state,
            lat: isset($r['lat']) ? (float) $r['lat'] : null,
            lon: isset($r['lon']) ? (float) $r['lon'] : null,
            confidence: $confidence,
        );
    }
}
