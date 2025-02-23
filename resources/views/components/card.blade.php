@props([
'title' => null,
'buttons' => null,
'border' => null,
'bg' => null,
'mb' => '4',
])

@php
    $attributes = $attributes->class([
        'card',
        'mb-'.$mb,
        'border border-' . $border => $border,
        'bg-' . $bg => $bg,
    ])->merge([
        //
    ]);
@endphp

<div {{ $attributes->only('class') }}>
    <div class="card-body shadow">
        <x-rpd::card-header :title="$title" :buttons="$buttons" />
       {{ $slot }}
    </div>
</div>


