@props([
'title' => null,
'buttons' => null,
])

<div class="d-flex justify-content-between align-items-center">
    <x-rpd::heading :title="$title" style="muted" />
    <x-rpd::button-group :buttons="$buttons" />
</div>
