@props([
'label' => null,
'type' => 'text',
'icon' => null,
'prepend' => null,
'append' => null,
'size' => null,
'help' => null,
'model' => null,
'debounce' => false,
'lazy' => false,
'col'  => null,
])

@php

    $required = false;
    if($model && property_exists($this,'rules') && isset($this->rules[$model]) && stristr($this->rules[$model],'required')) {
       $required = true;
    }

    if ($type == 'number') $inputmode = 'decimal';
    else if (in_array($type, ['tel', 'search', 'email', 'url'])) $inputmode = $type;
    else $inputmode = 'text';
    if ($debounce) $bind = 'live.debounce.' . (ctype_digit($debounce) ? $debounce : 150) . 'ms';
    else if ($lazy) $bind = 'lazy';
    else $bind = 'live.debounce.150ms';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix =  null;

    $attributes = $attributes->class([
        'form-control',
        'form-control-' . $size => $size,
        'rounded-end' => !$append,
        'is-invalid' => $errors->has($key),
    ])->merge([
        'type' => $type,
        'inputmode' => $inputmode,
        'id' => $id,
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);
@endphp

<div class="{{$col}}">
    <x-rpd::label :for="$id" :label="$label" :required="$required"/>

    <div class="input-group">
        <x-rpd::input-addon :icon="$icon" :label="$prepend"/>

        <input {{ $attributes }}>

        <x-rpd::input-addon :label="$append" class="rounded-end"/>

        <x-rpd::error :key="$key"/>
    </div>

    <x-rpd::help :label="$help"/>
</div>




