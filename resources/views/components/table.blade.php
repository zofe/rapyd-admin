@props([
'title' => null,
'buttons' => null,
'items'=> null,
'pagination'=> null,
'filters'=>null,
'actions'=>null,
'marginbottom' => 1
])
<div class="rpd-table">
    @if($title || $buttons || $filters)
    <form autocomplete="off">
        <div class="d-flex @if($marginbottom)mb-4 @endif">
            <div class="flex-grow-1">
                <div class="row g-2">
                    <x-rpd::heading :title="$title" />

                    {{ $filters }}

                    <x-rpd::button-group :buttons="$buttons" />
                </div>
            </div>
        </div>
    </form>
    @endif
    <div class="table row mb-1">
        <div class="table-responsive">
            {{ $slot }}
        </div>
    </div>

    <div class="d-flex">
        <div class="flex-grow-1">
            <div class="row g-2">
                @php
                /** @var $items \Illuminate\Pagination\LengthAwarePaginator */
                @endphp
                <x-rpd::pagination :items="$items" />

                <x-rpd::button-group :buttons="$actions" position="center" />
            </div>
        </div>
    </div>

</div>
