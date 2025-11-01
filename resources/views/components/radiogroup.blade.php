@props([
'label' => null,
'options' => [],
'size' => null,
'help' => null,
'model' => null,
'lazy' => false,
'col'  => null,
'chunk'=> 5,
'horizontal' => false,
])

@php
    if ($lazy) $bind = 'lazy';
    else $bind = 'defer';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $options = Arr::isAssoc($options) ? $options : array_combine($options, $options);
    $attributes = $attributes->class([
        'form-check-input',
        'is-invalid' => $errors->has($key),

    ])->merge([
        'name'=>$key,
        'type'=>'radio',
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);
@endphp


@foreach (collect($options)->chunk($chunk) as $options)
<div class="{{$col}} {{ $horizontal ? 'd-flex gap-3 flex-wrap' : '' }}">

        @foreach($options as $optionValue => $optionLabel)
            @php
               $optionId = \Illuminate\Support\Str::snake($id.'_'.$optionValue);
               $optionAttributes = $attributes->merge(['id'=>$optionId,'value'=>$optionValue]);

            @endphp

            <div class="form-check">
                <input {{ $optionAttributes }} >
                <x-rpd::check-label :for="$optionId" :label="$optionLabel"/>
            </div>

        @endforeach

        <x-rpd::error :key="$key"/>


    <x-rpd::help :label="$help"/>
</div>
@endforeach
