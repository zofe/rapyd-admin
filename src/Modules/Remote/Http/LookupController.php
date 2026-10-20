<?php

namespace Zofe\Rapyd\Modules\Remote\Http;

use Illuminate\Http\Request;
use Zofe\Rapyd\Modules\Remote\Support\Remote;

/** Feeds <x-remote::select>: [{id, title}] for TomSelect */
class LookupController
{
    public function __invoke(Request $request, string $source, string $resource)
    {
        return collect(Remote::search("{$source}.{$resource}", (string) $request->query('q')))
            ->map(fn ($r) => ['id' => $r->remote_id, 'title' => $r->label]);
    }
}
