@props([
'label' => null,
'help' => null,
'model' => null,
'format' => "dd/MM/yyyy",
'value-format' => "yyyy-MM-dd",
])

@php
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $attributes = $attributes->class([
    ])->merge([
        //'id' => $id,
        'format' => $format,
        'value-format' =>  ${'value-format'},
        'model' => $model ? $prefix . $model : null,
    ]);
@endphp

<div wire:ignore>
    <x-rpd::label :for="$id" :label="$label"/>

    <div class="input-group">

        <rpd-date
            {{ $attributes }}
        ></rpd-date>

    </div>

    <x-rpd::help :label="$help"/>
</div>
