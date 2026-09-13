<?php

namespace Zofe\Rapyd\Modules\Addresses\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zofe\Rapyd\Traits\ShortId;

class Address extends Model
{
    use HasUuids;
    use SoftDeletes;
    use ShortId;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'address', 'street_number', 'zipcode', 'city', 'province', 'region',
        'country', 'country_code', 'state_code', 'address_lat', 'address_lon',
        'verified_by', 'verified_at', 'confidence',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'address_lat' => 'float',
        'address_lon' => 'float',
    ];

    public function addressable()
    {
        return $this->morphTo();
    }
}
