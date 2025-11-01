@props([
'name' => null,
'style' => 'solid',
'size' => null,
'color' => null,
'spin' => false,
'pulse' => false,
'dismiss' => null,
'toggle' => null,
'target' => null,
'click' => null,
'route' => null,
'url' => null,
'href' => null,
'params' => [],
'confirm' => false,
'message' => null,
'label' => null,
])

@php
    $message = $confirm ? __($confirm) : __('Are you sure?');
    if($click) {
        $click = strpos($click,'(') ? $click : $click.'()';
    }
    if($route) {
        $href = item_href($route, $params, $url);
    }

    $attributes = $attributes->class([
        'fa' . Str::limit($style, 1, null) . ' fa-fw fa-' . $name,
        'fa-' . $size => $size,
        'text-' . $color => $color,
        'fa-spin' => $spin,
        'fa-pulse' => $pulse,
    ])->merge([
        'data-bs-dismiss' => $dismiss,
        'data-bs-target' => $target ? '#'.$target.'Modal':null,
        'data-bs-toggle' => $target ? 'modal' : $toggle,
        'wire:click' => $confirm ? null : $click,
        'x-data' => $confirm && $click ? "{}" : null,
        'x-on:click' => $confirm && $click ? "modbox.confirm({body: '".htmlspecialchars($message,ENT_QUOTES)."',okButton: {label: 'Confirm'}}).then(() => {  \$wire.$click; })" : null,
        'role' => $click || $target || $href ? "button" : 'null',
    ]);
@endphp

@if($name)
    @if($href)
        <a href="{{ $href }}" class="text-decoration-none text-reset">
            <i {{ $attributes }}></i>  {{ $label ?? $slot }}
        </a>
    @else
        <i {{ $attributes }}></i>  {{ $label ?? $slot }}
    @endif
@endif
