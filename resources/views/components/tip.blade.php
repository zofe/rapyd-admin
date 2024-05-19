@props([
'title' => null,
'icon' => "far fa-question-circle",
])

@php
    $attributes = $attributes->class([
        'form-text',
    ])->merge([
        "title" => $title,
        "data-toggle"=>"tooltip" ,
        "data-placement"=>"top"
    ]);
@endphp


<span {{ $attributes }}>
    <i class="{{ $icon }} text-primary"></i>
</span>

