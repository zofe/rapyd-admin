@props([
'title' => null,
'filters'=> null,
'buttons' => null,
'actions' => null,
'items'=> null,
'pagination'=> null,
'classTitle' => null,
'btnId' => null
])
@php
@endphp
<div class="rpd-view" {{$attributes->only('x-data')}}>
    <form autocomplete="off">
        <div class="d-flex mb-4">
            <x-rpd::heading :title="$title" :classTitle="$classTitle" />
            {{ $filters }}
            <x-rpd::button-group :buttons="$buttons" :id="$btnId" />
        </div>
    </form>
    <div>
        {{ $slot }}
    </div>

    <div class="d-flex">
        <div class="flex-grow-1">
            <div class="row g-2">
                <x-rpd::pagination :items="$items" />
                <x-rpd::button-group :buttons="$actions" position="center" />
            </div>
        </div>
    </div>

</div>
