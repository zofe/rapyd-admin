@props([
    'icon' => null,
    'label' => null,
    'route' => null,
    'url' => null,
    'href' => null,
    'click' => null,
    'params' => [],
    'active' => false,
    'type'  => 'nav-link',
    'fromItem' => false,
])

@php
    if ($route) {
        $href = route_lang($route, $params);
        $active = $active ?: request()->routeIs($route);
    } else if ($url){
        $href = url($url);
    }

    if($fromItem) {
        $active = false;
    }

    $attributes = $attributes->class([
        $type,
        'active' => $active,
    ])->merge([
        'href' => $href,
        'wire:click.prevent' => $click,
    ]);
@endphp
<a {{ $attributes }}>
    <x-rpd::icon :name="$icon"/>
    <span class="text-capitalize pt-1">{{ __($label) ?? $slot }}</span>
</a>
