{{-- The steps back of a workflow ('kind' => 'back') in one small "Back" menu: a quiet link, smaller than the buttons.
     Expects $backs (transitions), $entity, $labelOf and $clickOf from the embed. --}}
<div class="dropdown">
    <a href="#" class="dropdown-toggle small text-decoration-none text-body-secondary" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: .75rem;">
        <i class="fas fa-undo me-1"></i>{{ __('Back') }}
    </a>
    <ul class="dropdown-menu dropdown-menu-end small">
        @foreach($backs as $transition)
            @php $action = $entity->workflow_metadata('action', $transition->getName(), false); @endphp
            <li>
                @if($action)
                    <a class="dropdown-item" href="#" x-data x-on:click.prevent="{!! $clickOf($transition->getName()) !!}">{{ __($labelOf($transition->getName())) }}</a>
                @else
                    <a class="dropdown-item" href="#" x-data x-on:click.prevent="modbox.confirm({body: '{{ addslashes(__('Go back a step?')) }}', okButton: {label: 'Confirm'}}).then(() => { $wire.{{ $clickOf($transition->getName()) }}; })">{{ __($labelOf($transition->getName())) }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</div>
