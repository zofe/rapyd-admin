<?php

namespace Zofe\Rapyd\Modules\Addresses\Lookup;

/** One suggestion of an address lookup, already split into the fields of the Address model. */
final class AddressCandidate
{
    public const VERIFIED = 'verified';  // matched down to the building / house number
    public const PARTIAL = 'partial';    // matched a street, a postcode or a city only

    public function __construct(
        public readonly string $label,
        public readonly ?string $address = null,
        public readonly ?string $street_number = null,
        public readonly ?string $zipcode = null,
        public readonly ?string $city = null,
        public readonly ?string $province = null,
        public readonly ?string $region = null,
        public readonly ?string $country = null,
        public readonly ?string $country_code = null,
        public readonly ?string $state_code = null,
        public readonly ?float $lat = null,
        public readonly ?float $lon = null,
        public readonly string $confidence = self::PARTIAL,
    ) {
    }

    /** The attributes to fill on an Address. */
    public function attributes(): array
    {
        return [
            'address'       => $this->address,
            'street_number' => $this->street_number,
            'zipcode'       => $this->zipcode,
            'city'          => $this->city,
            'province'      => $this->province,
            'region'        => $this->region,
            'country'       => $this->country,
            'country_code'  => $this->country_code ? strtoupper($this->country_code) : null,
            'state_code'    => $this->state_code ? strtoupper($this->state_code) : null,
            'address_lat'   => $this->lat,
            'address_lon'   => $this->lon,
        ];
    }

    public function toArray(): array
    {
        return ['label' => $this->label, 'confidence' => $this->confidence] + $this->attributes();
    }

    public static function fromArray(array $a): self
    {
        return new self(
            label: $a['label'] ?? '',
            address: $a['address'] ?? null,
            street_number: $a['street_number'] ?? null,
            zipcode: $a['zipcode'] ?? null,
            city: $a['city'] ?? null,
            province: $a['province'] ?? null,
            region: $a['region'] ?? null,
            country: $a['country'] ?? null,
            country_code: $a['country_code'] ?? null,
            state_code: $a['state_code'] ?? null,
            lat: isset($a['address_lat']) ? (float) $a['address_lat'] : null,
            lon: isset($a['address_lon']) ? (float) $a['address_lon'] : null,
            confidence: $a['confidence'] ?? self::PARTIAL,
        );
    }
}
