@props([
'label' => null,
'icon' => null,
'prepend' => null,
'append' => null,
'rows' => 3,
'size' => null,
'help' => null,
'model' => null,
'debounce' => false,
'lazy' => false,
'col'  => null,
])

@php
    if ($debounce) $bind = 'live.debounce.' . (ctype_digit($debounce) ? $debounce : 150) . 'ms';
    else if ($lazy) $bind = 'lazy';
    else $bind = 'defer';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $attributes = $attributes->class([
        'form-control',
        'form-control-' . $size => $size,
        'rounded-end' => !$append,
        'is-invalid' => $errors->has($key),
    ])->merge([
        'id' => $id,
        'rows' => $rows,
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);
@endphp

<div class="{{$col}}">
    <x-rpd::label :for="$id" :label="$label"/>

    <div class="input-group">
        <x-rpd::input-addon :icon="$icon" :label="$prepend"/>

        <textarea {{ $attributes }}></textarea>

        <x-rpd::input-addon :label="$append" class="rounded-end"/>

        <x-rpd::error :key="$key"/>
    </div>

    <x-rpd::help :label="$help"/>
</div>



