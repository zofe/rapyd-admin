@props([
'key' => null,
])

@php
    $attributes = $attributes->class([
        'invalid-feedback',
    ])->merge([
        //
    ]);
@endphp

@error($key)
<div>
    <div class="is-invalid"></div>
    <div {{ $attributes }}>
        {{ $message }}
    </div>
</div>

@enderror
