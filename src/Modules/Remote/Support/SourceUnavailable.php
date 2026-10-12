<?php

namespace Zofe\Rapyd\Modules\Remote\Support;

use RuntimeException;

/**
 * The source did not answer, or answered with an error.
 *
 * Thrown by Remote::fetch() and caught by the callers, which fall back to the last known
 * copies. After one of these the source is left alone for `rapyd.remote.retry_after`
 * seconds: a source that is down would otherwise cost every page a request and a timeout.
 */
class SourceUnavailable extends RuntimeException
{
    public static function for(string $source, string $reason): self
    {
        return new self("Remote source [{$source}] is unavailable: {$reason}");
    }
}
