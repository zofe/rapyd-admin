@props([
'label' => null,
'checkLabel' => null,
'help' => null,
'switch' => false,
'model' => null,
'lazy' => false,
'col' => null,
])

@php
    if ($lazy) $bind = 'lazy';
    else $bind = 'defer';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $attributes = $attributes->class([
        'form-check-input',
        'is-invalid' => $errors->has($key),
    ])->merge([
        'type' => 'checkbox',
        'id' => $id,
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);
@endphp

<div class="{{$col}}">
    <x-rpd::label :label="$label"/>

    <div class="form-check {{ $switch ? 'form-switch' : '' }}">

        <input {{ $attributes }}>

        <x-rpd::check-label :for="$id" :label="$checkLabel"/>

        {{ $slot }}

        <x-rpd::error :key="$key"/>

        <x-rpd::help :label="$help"/>
    </div>
</div>



