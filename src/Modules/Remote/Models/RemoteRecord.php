<?php

namespace Zofe\Rapyd\Modules\Remote\Models;

use Illuminate\Database\Eloquent\Model;

/** Local copy of an entity owned by another app: $record->label, $record->payload['vat_number']. Read-only. */
class RemoteRecord extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'fetched_at' => 'datetime'];
}
