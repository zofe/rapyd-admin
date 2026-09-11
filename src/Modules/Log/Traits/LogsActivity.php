<?php

namespace Zofe\Rapyd\Modules\Log\Traits;

use Zofe\Rapyd\Modules\Log\LogOptions;

/**
 * Records created/updated/deleted on the model into the activity log.
 * Override getActivitylogOptions() to pick the attributes (logOnly, logExcept...).
 */
trait LogsActivity
{
    use \Spatie\Activitylog\Traits\LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName(str(class_basename($this))->snake()->toString());
    }
}
