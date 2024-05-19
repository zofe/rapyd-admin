@props([
'label' => null,
'help' => null,
'model' => null,
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $attributes = $attributes->class([
//        'format' => "dd/MM/yyyy HH:mm:ss",
//        'value-format' => "yyyy-MM-dd HH:mm:ss",
    ])->merge([
        'id' => $id,
        'model' => $model ? $prefix . $model : null,
    ]);
@endphp

<div wire:ignore>
    <x-rpd::label :for="$id" :label="$label"/>

    <div class="input-group">

        <rpd-time-select
            {{ $attributes }}
        ></rpd-time-select>

    </div>

    <x-rpd::help :label="$help"/>
</div>
