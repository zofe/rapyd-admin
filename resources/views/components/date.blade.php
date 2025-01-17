@props([
'label' => null,
'help' => null,
'model' => null,
'size' => null,
'lazy' => true,
'col'  => null,
])

@php
    if($lazy) {
        $bind = 'lazy';
    } else {
        $bind = 'live.debounce.150ms';
    }
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $attributes = $attributes->class([
        'form-control',
        'form-control-' . $size => $size,
        'rounded-end',
        'is-invalid' => $errors->has($key),
    ])->merge([
        //'id' => $id,
        'type' => "date",
        'wire:model.' . $bind => $model ? $prefix . $model : null,
        'class' => 'form-control rounded-end'
    ]);
@endphp


<div class="{{$col}}">
    <x-rpd::label :for="$id" :label="$label"/>

    <div class="input-group">
        <input
            {{ $attributes }}
        >
    </div>
    <x-rpd::force-error :key="$key" />

    <x-rpd::help :label="$help"/>

</div>
