<?php

namespace Zofe\Rapyd\Traits;

trait ShortId
{
    public function getShortIdAttribute()
    {
        // Last 8 hex chars: with ordered (v7) UUIDs the leading ones are a timestamp
        // shared by every record created in the same ~65 seconds.
        return strtoupper(substr((string) $this->getKey(), -8));
    }
}
