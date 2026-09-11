<?php

namespace Zofe\Rapyd\Modules\Log;

class LogOptions extends \Spatie\Activitylog\LogOptions
{
    public static function defaults(): self
    {
        return (new self())->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
