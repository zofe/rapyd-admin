<?php

namespace App\Modules\Search\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Zofe\Rapyd\Modules\Auth\Traits\Limit;

class SearchController extends Controller
{
    use Limit;

    public function search()
    {
        $term = trim((string) request()->query('q'));
        if ($term === '') {
            return response()->json([]);
        }

        $this->limit();

        $items = collect();
        foreach (config('rapyd.search.models', []) as $entity) {
            if (! class_exists($entity['class']) || ! Route::has($entity['route'])) {
                continue;
            }
            $scope = $entity['scope'] ?? 'ssearch';
            $label = $entity['label'] ?? 'name';
            $icon = $entity['icon'] ?? 'search';

            $found = $entity['class']::$scope($term, $entity['limit'] ?? 5)->get()->map(function ($model) use ($entity, $label, $icon) {
                $model->item_label = $label;
                $model->item_icon = $icon;
                $item = new \stdClass();
                $item->id = $model->getKey();
                $item->url = route_lang($entity['route'], $model->getKey());
                $item->html = view($entity['view'] ?? 'search::item', ['item' => $model])->render();

                return $item;
            });
            $items = $items->merge($found);
        }

        return response()->json($items->values());
    }
}
