<?php

namespace Zofe\Rapyd\Modules\Log\Models;

use Spatie\Activitylog\Models\Activity as SpatieActivity;

class Activity extends SpatieActivity
{
    /**
     * Property changes as rows of [key, old, new], ready to display.
     * Model events store {attributes, old}; log_activity() stores a flat array.
     */
    public function readableChanges(): array
    {
        $props = $this->properties?->toArray() ?? [];
        $hidden = config('rapyd.log.activity.hidden_properties', []);
        $rows = [];

        if (isset($props['attributes']) && is_array($props['attributes'])) {
            foreach ($props['attributes'] as $key => $new) {
                if (in_array($key, $hidden)) {
                    continue;
                }
                $rows[] = ['key' => $key, 'old' => $props['old'][$key] ?? null, 'new' => $new];
            }

            return $rows;
        }

        foreach ($props as $key => $value) {
            if (in_array($key, $hidden)) {
                continue;
            }
            $rows[] = ['key' => $key, 'old' => null, 'new' => $value];
        }

        return $rows;
    }
}
