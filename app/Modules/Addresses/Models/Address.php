<?php
namespace App\Modules\Addresses\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zofe\Rapyd\Traits\ShortId;

class Address extends Model
{
    use HasUuids, SoftDeletes, ShortId;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'address',
        'street_number',
        'zipcode',
        'city',
        'province',
        'region',
        'country',
        'country_code',
        'address_lat',
        'address_lon',
    ];

    // Relazione polimorfica
    public function addressable()
    {
        return $this->morphTo();
    }
}
