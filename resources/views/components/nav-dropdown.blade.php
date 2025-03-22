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
    //$label = __($label);

    $identifier= \Illuminate\Support\Str::studly($label);

    $active = item_active($active, $route, $params, $url);

     $attributes = $attributes->class([
         'nav-link',
         'collapsed' => !$active,
     ]);
@endphp
<li class="nav-item">

    <a href="#"
       data-bs-toggle="collapse"
       data-bs-target="#collapse{{$identifier}}"
       aria-expanded="false"
       aria-controls="collapse{{$identifier}}"
        {{ $attributes->only('class') }}>

        <x-rpd::icon :name="$icon"/>
        <span class="text-capitalize pt-1">{{ $label }}</span>
    </a>
    <div id="collapse{{$identifier}}" data-parent="#accordionSidebar" class="collapse {{ $active ? 'show' : '' }}" style="">
        <div class="ps-2 pb-1 small collapse-inner rounded">
            {{ $slot }}
        </div>
    </div>

</li>
