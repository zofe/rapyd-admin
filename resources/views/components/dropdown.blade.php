@props([
'label' => null,
'color' => 'primary',
'size' => null,
'type' => 'button',
'form' => false,
'action' => null,
])

@php
    $label = $label ? __($label) : null;

    $attributes = $attributes->class([
        'btn btn-' . $color,
        'btn-' . $size => $size,
        'dropdown-toggle'
    ])->merge([
        'type' => 'button',
        'data-bs-toggle' => "dropdown",
        'aria-expanded' => "false",
        'data-bs-auto-close' =>"outside"
    ]);
@endphp

<div class="dropdown">
    <button {{ $attributes }}>
        {{ $label }}
    </button>
    <ul class="dropdown-menu">
        @if($action)
            <form wire:submit.prevent="{{$action}}" autocomplete="off">
                <div class="px-2 pb-1">
                    {!! $slot !!}
                    <div>
                        <input type="submit" class="btn btn-primary" role="button" value="Confirm">
                    </div>
                </div>

            </form>
        @else
            {!! $slot !!}
        @endif
    </ul>

</div>



