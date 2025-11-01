@php
    $breadcrumbs = $generate();
@endphp
@if($breadcrumbs->count() > 1)
<nav aria-label="breadcrumb" wire:ignore>
    <ol class="breadcrumb pr-3">
        @foreach ($breadcrumbs as $crumbs)
            @if ($crumbs->url() && !$loop->last)
                <li class="{{$class}}">
                    <a href="{{ $crumbs->url() }}">{{ $crumbs->title() }}</a>
                </li>
            @else
                <li class="{{$class}} {{$active}}">{{ $crumbs->title() }}</li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
