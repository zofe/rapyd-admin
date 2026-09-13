<?php

return [
    'enabled' => env('RAPYD_ADDRESSES_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Address lookup
    |--------------------------------------------------------------------------
    | With a Google Maps Platform key (Places API New + Address Validation API
    | enabled) the address form gets a search box: type a few words, pick a
    | suggestion, the fields are filled (street, number, postcode, city, region,
    | ISO country, state, coordinates) and the address is marked as verified.
    | Fields stay editable. Without a key: manual fields only.
    |
    |   lookup   : google | none | a class implementing Lookup\Contracts\AddressLookup
    |   validate : false | true | 'strict'. On save, Google Address Validation says
    |              whether the address exists as typed; 'strict' refuses an
    |              address it cannot find (physical shipments).
    */
    'google_key'     => env('GOOGLE_MAPS_KEY'),
    'lookup'         => env('RAPYD_ADDRESS_LOOKUP', env('GOOGLE_MAPS_KEY') ? 'google' : 'none'),
    'validate'       => env('RAPYD_ADDRESS_VALIDATE', false),
    'lookup_lang'    => env('RAPYD_ADDRESS_LOOKUP_LANG'),      // ISO 639-1, null = the app locale
    'lookup_country' => env('RAPYD_ADDRESS_LOOKUP_COUNTRY'),   // optional ISO country bias, e.g. "it"
    'lookup_limit'   => 5,
];
