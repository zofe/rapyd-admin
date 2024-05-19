@props([
'label' => null,
'placeholder' => null,
'options' => [],
'icon' => null,
'prepend' => null,
'append' => null,
'size' => null,
'help' => null,
'model' => null,
'lazy' => false,
'col'  => null,
])

@php
    $required = false;
    if($model && property_exists($this,'rules') && isset($this->rules[$model]) && stristr($this->rules[$model],'required')) {
       $required = true;
    }

    if ($lazy) $bind = 'lazy';
    else $bind = 'defer';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $options = Arr::isAssoc($options) ? $options : array_combine($options, $options);
    $attributes = $attributes->class([
        'form-select',
        'form-select-' . $size => $size,
        'rounded-end' => !$append,
        'is-invalid' => $errors->has($key),
    ])->merge([
        'id' => $id,
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);
@endphp

<div class="{{$col}}">
    <x-rpd::label :for="$id" :label="$label" :required="$required"/>

    <div class="input-group">
        <x-rpd::input-addon :icon="$icon" :label="$prepend"/>

        <select {{ $attributes }}>
            <option value="">{{ $placeholder }}</option>

            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
            @endforeach
        </select>

        <x-rpd::input-addon :label="$append" class="rounded-end"/>

        <x-rpd::error :key="$key"/>
    </div>

    <x-rpd::help :label="$help"/>
</div>
