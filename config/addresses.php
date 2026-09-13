<?php

return [
    'enabled' => env('RAPYD_ADDRESSES_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Address lookup
    |--------------------------------------------------------------------------
    | The search box of the address form: type a few words, pick a suggestion,
    | the fields (street, number, postcode, city, region, ISO country, state,
    | coordinates) are filled and the address is marked as verified by the
    | driver. Fields stay editable.
    |   none     : no search box, manual fields only
    |   geoapify : https://www.geoapify.com (free tier, key required)
    |   a class  : your own Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup
    */
    'lookup'         => env('RAPYD_ADDRESS_LOOKUP', 'none'),
    'geoapify_key'   => env('GEOAPIFY_KEY'),
    'lookup_lang'    => env('RAPYD_ADDRESS_LOOKUP_LANG'),      // ISO 639-1, null = the app locale
    'lookup_country' => env('RAPYD_ADDRESS_LOOKUP_COUNTRY'),   // optional ISO country bias, e.g. "it"
    'lookup_limit'   => 5,
];
