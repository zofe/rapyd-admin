@props([
'title' => null,
'name'  => 'modal',
'text'  => null,
'width' => 'lg',
'icon'  => null,
'buttons'=> null,
'action' => null,
'color'  => 'link',
'size'   => 'sm',
'onclick' => null,
'nobuttons' => false,
])
@php
    $attributes = $attributes->class([
            'btn btn-' . $color,
            'btn-' . $size => $size,
        ])
        //$text = $attributes->get('text', $title ?? $name);
@endphp
@if($icon || $text)
<a ref="#" {{$attributes->only('class') }} data-bs-toggle="modal" data-bs-target="#{{ $name }}Modal">
    @if($icon)
        <i class="{{ $icon }}"></i>
    @endif
    {{ $text }}
</a>
@endif
<form @if($action)wire:submit.prevent="{{$action}}"@endif autocomplete="off">
<div wire:ignore.self class="modal fade" id="{{ $name }}Modal" tabindex="1" role="dialog" aria-labelledby="{{ $name }}ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-{{ $width }}" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $name }}ModalLabel">{{ __($title) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>

            <div class="modal-footer">
                {{ $buttons }}
                @if(!$nobuttons)
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">{{ $action ? 'Cancel' : 'Close' }}</button>
                @if($action)<input type="submit" class="btn btn-primary" role="button" value="Confirm">@endif
                @if($onclick)<input onclick="{{$onclick}}" type="button" class="btn btn-primary" role="button" value="Confirm">@endif
                @endif
            </div>

        </div>
    </div>
</div>
</form>
