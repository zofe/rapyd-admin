<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup;
use Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressValidator;
use Zofe\Rapyd\Modules\Addresses\Models\Address;

/**
 * Google Maps Platform, server side (the key never reaches the browser):
 * - Places Autocomplete (New) while typing: suggestions are labels + place ids, grouped
 *   in a billing session (session usage is free) by the token the form generates;
 * - Place Details (New) for the chosen one: address components and coordinates
 *   (Essentials fields: 10,000 free a month, September 2026);
 * - Address Validation on save when config('rapyd.addresses.validate') is on:
 *   does the address exist as typed (5,000 free a month).
 * Key: GOOGLE_MAPS_KEY, with Places API (New) and Address Validation API enabled.
 */
class GoogleLookup implements AddressLookup, AddressValidator
{
    public const AUTOCOMPLETE = 'https://places.googleapis.com/v1/places:autocomplete';
    public const DETAILS = 'https://places.googleapis.com/v1/places/';
    public const VALIDATE = 'https://addressvalidation.googleapis.com/v1:validateAddress';

    public function name(): string
    {
        return 'google';
    }

    public function search(string $query, ?string $session = null): array
    {
        $key = config('rapyd.addresses.google_key');
        $query = trim($query);
        if (! $key || mb_strlen($query) < 3) {
            return [];
        }

        $body = ['input' => $query, 'languageCode' => $this->lang()];
        if ($session) {
            $body['sessionToken'] = $session;
        }
        if ($country = config('rapyd.addresses.lookup_country')) {
            $body['includedRegionCodes'] = [strtolower($country)];
        }

        $json = $this->call(fn () => Http::timeout(6)->withHeaders(['X-Goog-Api-Key' => $key])->post(self::AUTOCOMPLETE, $body), 'autocomplete');

        return collect($json['suggestions'] ?? [])
            ->map(fn ($s) => $s['placePrediction'] ?? null)->filter()
            ->take((int) config('rapyd.addresses.lookup_limit', 5))
            ->map(fn ($p) => new AddressCandidate(label: $p['text']['text'] ?? '', placeId: $p['placeId'] ?? null))
            ->values()->all();
    }

    public function resolve(AddressCandidate $candidate, ?string $session = null): AddressCandidate
    {
        $key = config('rapyd.addresses.google_key');
        if (! $key || ! $candidate->placeId) {
            return $candidate;
        }

        $params = ['languageCode' => $this->lang()];
        if ($session) {
            $params['sessionToken'] = $session;
        }
        $json = $this->call(fn () => Http::timeout(6)->withHeaders([
            'X-Goog-Api-Key'   => $key,
            'X-Goog-FieldMask' => 'addressComponents,location,formattedAddress',
        ])->get(self::DETAILS . $candidate->placeId, $params), 'place details');

        return $json ? $this->fromComponents($json['addressComponents'] ?? [], $json['location'] ?? [], $json['formattedAddress'] ?? $candidate->label, $candidate->placeId) : $candidate;
    }

    public function validate(Address $address): ?AddressCandidate
    {
        $key = config('rapyd.addresses.google_key');
        if (! $key || ! $address->country_code) {
            return null;
        }

        $body = ['address' => array_filter([
            'regionCode'         => strtoupper($address->country_code),
            'languageCode'       => $this->lang(),
            'postalCode'         => $address->zipcode,
            'locality'           => $address->city,
            'administrativeArea' => $address->state_code ?: $address->region,
            'addressLines'       => [trim($address->address . ' ' . $address->street_number)],
        ])];

        $json = $this->call(fn () => Http::timeout(8)->post(self::VALIDATE . '?key=' . $key, $body), 'address validation');
        if (! $json || ! isset($json['result'])) {
            return null;
        }

        $verdict = $json['result']['verdict'] ?? [];
        $granularity = $verdict['validationGranularity'] ?? 'OTHER';
        $confidence = match (true) {
            in_array($granularity, ['PREMISE', 'SUB_PREMISE']) && empty($verdict['hasUnconfirmedComponents']) => AddressCandidate::VERIFIED,
            in_array($granularity, ['PREMISE', 'SUB_PREMISE', 'PREMISE_PROXIMITY', 'BLOCK', 'ROUTE']) => AddressCandidate::PARTIAL,
            default => AddressCandidate::UNKNOWN,
        };

        $components = [];
        foreach ($json['result']['address']['addressComponents'] ?? [] as $c) {
            $components[] = ['longText' => $c['componentName']['text'] ?? '', 'shortText' => $c['componentName']['text'] ?? '', 'types' => [$c['componentType'] ?? '']];
        }
        $geo = $json['result']['geocode']['location'] ?? [];
        $candidate = $this->fromComponents($components, $geo, $json['result']['address']['formattedAddress'] ?? '', null, $confidence);

        // the validation gives the country as a name only: keep the code the form had
        return new AddressCandidate(...array_merge((array) $candidate, ['country_code' => strtoupper($address->country_code), 'confidence' => $confidence]));
    }

    /** Google's addressComponents (Place Details / Address Validation) to an AddressCandidate. */
    public function fromComponents(array $components, array $location, string $label, ?string $placeId = null, ?string $confidence = null): AddressCandidate
    {
        $get = function (string $type, string $field = 'longText') use ($components) {
            foreach ($components as $c) {
                if (in_array($type, $c['types'] ?? [])) {
                    return $c[$field] ?? null;
                }
            }
            return null;
        };
        $number = $get('street_number');
        $city = $get('locality') ?? $get('postal_town') ?? $get('administrative_area_level_3') ?? $get('sublocality_level_1');
        $provinceShort = $get('administrative_area_level_2', 'shortText');
        $countryCode = $get('country', 'shortText');
        $stateShort = $get('administrative_area_level_1', 'shortText');

        return new AddressCandidate(
            label: $label,
            address: $get('route') ?? $get('premise'),
            street_number: $number,
            zipcode: $get('postal_code'),
            city: $city,
            province: $provinceShort && mb_strlen($provinceShort) <= 4 ? $provinceShort : $get('administrative_area_level_2'),
            region: $get('administrative_area_level_1'),
            country: $get('country'),
            country_code: $countryCode,
            state_code: $stateShort && mb_strlen($stateShort) <= 4 && $stateShort !== $get('administrative_area_level_1') ? $stateShort : null,
            lat: isset($location['latitude']) ? (float) $location['latitude'] : null,
            lon: isset($location['longitude']) ? (float) $location['longitude'] : null,
            confidence: $confidence ?? ($number ? AddressCandidate::VERIFIED : AddressCandidate::PARTIAL),
            placeId: $placeId,
        );
    }

    protected function call(\Closure $request, string $what): ?array
    {
        try {
            $response = $request();
            if (! $response->ok()) {
                Log::warning("google {$what}: " . $response->status(), ['body' => mb_substr($response->body(), 0, 300)]);
                return null;
            }
            return $response->json();
        } catch (\Throwable $e) {
            Log::warning("google {$what}: unreachable", ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function lang(): string
    {
        return config('rapyd.addresses.lookup_lang') ?: substr(app()->getLocale(), 0, 2);
    }
}
