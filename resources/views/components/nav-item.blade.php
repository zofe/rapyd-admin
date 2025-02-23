@props([
'icon' => null,
'label' => null,
'route' => null,
'url' => null,
'href' => null,
'click' => null,
'params' => [],
'active' => false,
])
@php


    $active = item_active($active, $route, $params, $url);
    $href = item_href($route, $params, $url);
//     if (strpos($active,'|')) {
//         $actives = explode('|', $active);
//     } else {
//         $actives[] = $active;
//     }
//
//     foreach ($actives as $active) {
//
//         if(is_string($active) && strlen($active)>2 && !in_array(strtolower($active),['true','false']) ) {
//             $active = url_contains($active);
//         }
//         if ($route) {
//             $href = route_lang($route, $params);
//             $active = $active ?: request()->routeIs($route);
//         } else if ($url){
//             $href = url($url);
//             $active = $active  ?: $href == url()->current();
//         }
//
//         if ($active) {
//             break;
//         }
//
//     }

@endphp

<li class="nav-item {{ $active ? 'active' : '' }}">
    <x-rpd::nav-link
        :icon="$icon"
        :label="$label"
        :route="$route"
        :url="$url"
        :href="$href"
        :click="$click"
        :params="$params"
        :attributes="$attributes"
        fromItem="1"
    />
</li>
