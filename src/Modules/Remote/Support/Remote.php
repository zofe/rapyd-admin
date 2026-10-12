<?php

namespace Zofe\Rapyd\Modules\Remote\Support;

use Zofe\Rapyd\Modules\Remote\Models\RemoteRecord;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Consumer side. References are "source.resource", e.g. "crm.customers".
 *
 *   Remote::find('crm.customers', 12)            -> RemoteRecord|null
 *   Remote::many('crm.customers', [12, 15])      -> [id => RemoteRecord]
 *   Remote::search('crm.customers', 'acme')      -> RemoteRecord[]
 *
 * Records are cached in remote_records: fresh ones are read locally, the others are fetched in one call.
 * If the source is down, the last known copy is used.
 */
class Remote
{
    public static function find(string $ref, $id): ?RemoteRecord
    {
        return blank($id) ? null : (static::many($ref, [$id])[(string) $id] ?? null);
    }

    public static function many(string $ref, iterable $ids): array
    {
        [$source, $resource] = explode('.', $ref, 2);
        $ids = collect($ids)->filter(fn ($id) => filled($id))->map(fn ($id) => (string) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        $cached = RemoteRecord::where(compact('source', 'resource'))->whereIn('remote_id', $ids)->get()->keyBy('remote_id');
        $fresh = $cached->filter(fn ($r) => $r->fetched_at->gt(now()->subSeconds(config('rapyd.remote.ttl', 600))));
        $missing = $ids->diff($fresh->keys());

        if ($missing->isEmpty()) {
            return static::keyById($fresh);
        }

        try {
            $fetched = static::fetch($source, $resource, ['ids' => $missing->all()]);
        } catch (Throwable $e) {
            report($e);

            return static::keyById($cached); // source down: last known copies
        }

        return static::keyById($fresh) + static::keyById($fetched);
    }

    public static function search(string $ref, string $term): array
    {
        [$source, $resource] = explode('.', $ref, 2);
        try {
            return array_values(static::fetch($source, $resource, ['q' => $term]));
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Index records by their remote id, as a string.
     *
     * Callers look a record up by the id they asked for, so the key has to be that id and
     * nothing else: merging collections would renumber it, and the lookup would miss.
     *
     * @param  iterable<RemoteRecord>  $records
     * @return array<string, RemoteRecord>
     */
    protected static function keyById(iterable $records): array
    {
        $keyed = [];
        foreach ($records as $record) {
            $keyed[(string) $record->remote_id] = $record;
        }

        return $keyed;
    }

    /** Calls the source as the current user, stores the results, returns them keyed by id. */
    protected static function fetch(string $source, string $resource, array $query): array
    {
        $cfg = config("rapyd.remote.sources.{$source}");

        $items = Http::withToken($cfg['token'])
            ->withHeaders(['X-Remote-User' => (string) auth()->id()])
            ->timeout(3)
            ->get(rtrim($cfg['url'], '/') . "/_remote/{$resource}", $query)
            ->throw()
            ->json('data');

        $records = [];
        foreach ($items as $item) {
            $records[$item['id']] = RemoteRecord::updateOrCreate(
                ['source' => $source, 'resource' => $resource, 'remote_id' => $item['id']],
                ['label' => $item['label'], 'payload' => $item['attributes'], 'fetched_at' => now()],
            );
        }

        return $records;
    }
}
