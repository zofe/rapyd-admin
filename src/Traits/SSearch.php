<?php

namespace Zofe\Rapyd\Traits;

use Illuminate\Support\Facades\App;

trait SSearch
{
    public static function ssearch($search, $limit = null)
    {
        if (empty($search)) {
            return static::query();
        }

        //testing mode fallback
        if (App::environment(['testing']) || ! config('rapyd.search.use_scout', config('rapyd.use_scout', false))) {
            return static::ssearchFallback($search);
        }

        // Laravel Scout (Searchable on the model): pluck matching ids from the engine.
        if (method_exists(static::class, 'search')) {
            $matching = static::search($search, static::scoutCallback($limit));

            if ($limit) {
                $matching = $matching->take($limit);
            }
            $matching = $matching->get()->pluck('id');

            //restituisco mailisearch o una fallback
            if (count($matching)) {
                return static::query()->whereIn('id', $matching);
            }
        }

        return static::ssearchFallback($search, $limit);
    }

    /**
     * Engine-specific search options. With Meilisearch only ids are retrieved and the
     * model can add its own parameters through static::$searchOptions
     * (filter, attributesToSearchOn, ...). Other engines keep Scout's default behaviour.
     */
    protected static function scoutCallback($limit = null): ?\Closure
    {
        if (config('scout.driver') !== 'meilisearch') {
            return null;
        }

        return function ($engine, string $query, array $options) use ($limit) {
            $params = array_merge(
                ['limit' => $limit ?: 10, 'attributesToRetrieve' => ['id']],
                property_exists(static::class, 'searchOptions') ? static::$searchOptions : [],
                $options
            );

            return $engine->search($query, $params);
        };
    }

    // LIKE on static::$searchableColumns (default: name); override for anything smarter.
    public static function ssearchFallback($search, $limit = null)
    {
        $columns = property_exists(static::class, 'searchableColumns') ? static::$searchableColumns : ['name'];

        $items = static::query()->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', '%' . $search . '%');
            }
        });
        if ($limit) {
            $items = $items->limit($limit);
        }

        return $items;
    }
}
