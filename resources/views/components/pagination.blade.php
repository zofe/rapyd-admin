@props([
'items' => null,
'limits' => [5,10,20],
'count' => 'tot.',
])
@aware(['items'])
@if($items)

    @if(method_exists($items,'links'))
        <div class="d-flex justify-content-between mt-2">
            <div class="form-inline">
                {{ $items->links() }}
            </div>
            @if(is_array($limits) && count($limits) && $items->total()>$limits[0])
                <div class="form-inline">
                    <select wire:model="perPage" class="form-control">
                        <option>5</option>
                        <option>10</option>
                        <option>20</option>
                    </select>
                </div>
            @endif

            @if($count)
                <div class="form-inline d-flex justify-content-end text-right text-muted">
                    {{ $count }} {{ $items->total() }}
                </div>
            @endif
        </div>
    @endif
@endif
