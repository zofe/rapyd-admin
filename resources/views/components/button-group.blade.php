@props([
'buttons' => null,
'position' => 'end',
])
@php

    $width = ($position == 'center') ? 'w-100' : 'ms-auto';

    $attributes = $attributes->class([
        'btn-toolbar col-auto d-flex flex-column '.$width,
        'align-items-'. $position,
    ])->merge([

    ]);

    $buttonAttributes = optional($buttons)->attributes;
    if($buttonAttributes) {
      $buttonAttributes = $buttonAttributes->has('class') ? $buttonAttributes : $buttonAttributes->class(['']);//btn-group
    }

@endphp
<div {{ $attributes }} role="toolbar">
    <div {{ $buttonAttributes }}>
        {{ $buttons }}
    </div>
</div>
