<div class="space-y-4">
    {{-- Stato attuale --}}


    <div class="d-flex justify-content-around">

        @foreach ($entity->workflow_transition_blocked() as $transition_name => $messages)

            @if(implode('',$messages) != '')

                @php
                    $class = 'outline-secondary';
                    $label = $entity->workflow_metadata('label', $transition_name, false );
                    $action = $entity->workflow_metadata('action', $transition_name, false);

                    if($action) {
                      $click = '$dispatch(\''.$action."', { morphableType: '".$entity->getMorphClass()."', morphableId: '".$entity->id."'});";
                    } else {
                      $click = null;
                      $class = 'outline-secondary disabled';
                    }
                @endphp

                <div class="text-gray-500 text-center">

                    <x-rpd::button
                        label="{{ $label }}"
                        color="{{ $class }}"
                        click="{!!$click !!}"
                    />

                    @if(!$action)
                        @foreach($messages as $message)
                            <div>
                                <div class="small text-warning text-center">
                                    {{ $message }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            @endif

        @endforeach

    @foreach($entity->workflow_transitions() as $transition)

        @php
            $class = $entity->workflow_metadata('class', $transition, false)?? 'primary';
            $label = $entity->workflow_metadata('label', $transition, false );

            $action = $entity->workflow_metadata('action', $transition, false);
            if($action) {
              $click = '$dispatch(\''.$action."', { morphableType: '".$entity->getMorphClass()."', morphableId: '".$entity->id."'});";
            } else {
              $click = "applyTransition('".$transition->getName()."')";
            }
        @endphp
        <div>

            <x-rpd::button
                label="{{ $label }}"
                color="{{ $class }}"
                click="{!!$click !!}"
            />

        </div>

    @endforeach
    </div>


    @if($showHistory)
    <div>
        <table class="table table-sm small text-gray-500">
            @foreach($steps as $step)
                <tr>
                    <td>{{ implode(', ', array_keys($step->places ?? [])) }}</td>
                    <td>
                        @if($step->company)
                            {{ $step->company->business_name }}
                        @elseif($step->user)
                            {{ $step->user->name }}
                        @endif
                    </td>
                    <td class="text-nowrap text-end">{{ $step->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    @endif
</div>
