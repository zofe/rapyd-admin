<?php

namespace Zofe\Rapyd\Modules\Remote\Traits;

use Zofe\Rapyd\Modules\Remote\Models\RemoteRecord;
use Zofe\Rapyd\Modules\Remote\Support\Remote;

/**
 * On a model:
 *   use HasRemote;
 *   public function customer(): ?RemoteRecord { return $this->remote('crm.customers', 'customer_id'); }
 *
 *   public function services(): array { return $this->remoteMany('crm.services', 'service_ids'); }
 *
 * Usage: $device->customer()?->label, collect($device->services())->pluck('label')
 */
trait HasRemote
{
    public function remote(string $ref, string $foreignKey): ?RemoteRecord
    {
        return Remote::find($ref, $this->getAttribute($foreignKey));
    }

    /** For a json column holding several remote ids (cast it to array). */
    public function remoteMany(string $ref, string $column): array
    {
        return Remote::many($ref, $this->getAttribute($column) ?? []);
    }
}
