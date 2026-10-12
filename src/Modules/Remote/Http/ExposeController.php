<?php

namespace Zofe\Rapyd\Modules\Remote\Http;

use Zofe\Rapyd\Modules\Remote\Expose\RemoteExpose;
use Illuminate\Http\Request;

/** GET /_remote/{resource}?q=acme   or   ?ids[]=1&ids[]=2 */
class ExposeController
{
    public function __invoke(Request $request, string $resource)
    {
        $res = RemoteExpose::get($resource) ?? abort(404);

        $query = $res->model::query();
        if ($res->scope) {
            // The return value matters: a scope written as `fn ($q, $u) => $u ? $q : $q->whereRaw('1 = 0')`
            // hands back a different builder, and dropping it would publish rows nobody may see.
            // A scope that only narrows the builder in place may return nothing.
            $query = ($res->scope)($query, $request->attributes->get('remote_user')) ?? $query;
        }

        if ($request->filled('ids')) {
            $query->whereKey((array) $request->input('ids'));
        } else {
            $like = '%' . addcslashes((string) $request->input('q'), '%_\\') . '%';
            $query->where(fn ($q) => collect($res->searchable)->each(fn ($col) => $q->orWhere($col, 'like', $like)));
        }

        return ['data' => $query->limit(50)->get()->map(fn ($m) => $res->toArray($m))];
    }
}
