@props([
'icon' => null,
'iconStyle'=>'solid',
'label' => null,
'color' => 'primary',
'size' => null,
'type' => 'button',
'route' => null,
'url' => null,
'href' => null,
'dismiss' => null,
'toggle' => null,
'target' => null,
'click' => null,
'confirm' => false,
'message' => null,
])

@php
    if ($route) $href = route_lang($route);
    else if ($url) $href = url($url);
    $message = $confirm ? __($confirm) : __('Are you sure?');
    if($click) {
        $click = strpos($click,'(') ? $click : $click.'()';
    }

    $label = $label ? __($label) : null;

    $attributes = $attributes->class([
        'btn btn-' . $color,
        'btn-' . $size => $size,
    ])->merge([

        'type' => !$href ? $type : null,
        'href' => $href,
        'data-bs-dismiss' => $dismiss,
        'data-bs-target' => $target ? '#'.$target.'Modal':null,
        'data-bs-toggle' => $target ? 'modal' : $toggle,
        'wire:click' => $confirm ? null : $click,
        'x-data' => $confirm && $click ? "{}" : null,
        'x-on:click' => $confirm && $click ? "modbox.confirm({body: '".htmlspecialchars($message,ENT_QUOTES)."',okButton: {label: 'Confirm'}}).then(() => {  \$wire.$click; })" : null,
    ]);
@endphp

<{{ $href ? 'a' : 'button' }} {{ $attributes }}>
<x-rpd::icon :name="$icon" :style="$iconStyle"/>

{!! $label ?? $slot !!}
</{{ $href ? 'a' : 'button' }}>
