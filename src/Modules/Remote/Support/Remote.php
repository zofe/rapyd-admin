<?php

namespace Zofe\Rapyd\Modules\Remote\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;
use Zofe\Rapyd\Modules\Remote\Models\RemoteRecord;

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
            static::reportOnce($e);

            return static::keyById($cached); // source down: last known copies
        }

        return static::keyById($fresh) + static::keyById($fetched);
    }

    public static function search(string $ref, string $term): array
    {
        [$source, $resource] = explode('.', $ref, 2);

        // Below this the search would return the whole list on every keystroke.
        if (mb_strlen(trim($term)) < (int) config('rapyd.remote.min_search', 2)) {
            return [];
        }

        try {
            return array_values(static::fetch($source, $resource, ['q' => $term]));
        } catch (Throwable $e) {
            static::reportOnce($e);

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

    /** A source that just failed is left alone for a while, instead of costing every page a timeout. */
    protected static function downKey(string $source): string
    {
        return "rapyd:remote:down:{$source}";
    }

    /** Only the first failure of a down source is reported: the others would just fill the log. */
    protected static function reportOnce(Throwable $e): void
    {
        if (! $e instanceof SourceUnavailable || ! str_contains($e->getMessage(), 'waiting')) {
            report($e);
        }
    }

    /**
     * Who the source should answer for.
     *
     * By default the local user id, which works when the two apps share their users. When they do
     * not — a rapyd app reading from an application that has its own accounts — `user_attribute`
     * names the attribute holding the id that app knows (the OIDC `sub`, say).
     */
    protected static function actingUser(array $cfg): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $attribute = $cfg['user_attribute'] ?? null;

        return (string) ($attribute ? ($user->getAttribute($attribute) ?? '') : $user->getAuthIdentifier());
    }

    /** Calls the source as the current user, stores the results, returns them keyed by id. */
    protected static function fetch(string $source, string $resource, array $query): array
    {
        $cfg = config("rapyd.remote.sources.{$source}");

        if (Cache::has(static::downKey($source))) {
            throw SourceUnavailable::for($source, 'waiting before trying again');
        }

        try {
            $items = Http::withToken($cfg['token'])
                ->withHeaders(['X-Remote-User' => static::actingUser($cfg)])
                ->timeout((int) config('rapyd.remote.timeout', 3))
                ->get(rtrim($cfg['url'], '/') . "/_remote/{$resource}", $query)
                ->throw()
                ->json('data');
        } catch (Throwable $e) {
            Cache::put(static::downKey($source), true, (int) config('rapyd.remote.retry_after', 30));

            throw SourceUnavailable::for($source, $e->getMessage());
        }

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
