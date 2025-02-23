@props([
'title' => null,
'buttons' => null,
])

@if($title || $buttons)
<div class="d-flex">
    <div class="flex-grow-1">
        <div class="row pb-2">
            <x-rpd::heading :title="$title" style="muted" />
            <x-rpd::button-group :buttons="$buttons" />
        </div>
    </div>
</div>
@endif


