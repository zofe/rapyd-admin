@props([
'title' => null,
'icon' => null,
'h' => 4,
])
@if($title)
<div class="col heading d-flex justify-content-between">
    <h{{$h}}>@if($icon)<x-rpd::icon :name="$icon" />@endif{{ __($title) }}</h{{$h}}>
    {{ $slot }}
</div>
@endif
