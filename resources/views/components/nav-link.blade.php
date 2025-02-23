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

    $active = item_active($active, $route, $params, $url);
    $href = item_href($route, $params, $url);

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
