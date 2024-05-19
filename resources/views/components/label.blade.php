@props([
'label' => null,
'required' => false,
])

@php
    $label = __($label);
    $attributes = $attributes->class([
        'form-label'. ($required ? ' fw-bold':' '),
    ])->merge([
        //
    ]);
@endphp

@if($label || !$slot->isEmpty())
    <label {{ $attributes }}>
        {{ $label ?? $slot }}
    </label>
@endif
