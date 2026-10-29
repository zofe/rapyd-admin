<div>
    @php
        // The transitions of the moment, split by what they do: forward (the work), blocked (with the reason),
        // abort (cancel, fail: apart, outlined in red, behind a confirmation). 'kind' => 'abort' in the metadata.
        $transitions = collect($entity->workflow_transitions());
        // (string): the trait renders string metadata through Blade and hands back a View
        $aborts = $transitions->filter(fn ($t) => (string) $entity->workflow_metadata('kind', $t->getName(), false) === 'abort');
        $forward = $transitions->reject(fn ($t) => $aborts->contains($t));
        $blocked = collect($entity->workflow_transition_blocked())->filter(fn ($messages) => implode('', $messages) !== '');
        $labelOf = fn (string $name) => ucfirst($entity->workflow_metadata('label', $name, false) ?: str_replace('_', ' ', $name));
        $clickOf = function (string $name) use ($entity) {
            $action = $entity->workflow_metadata('action', $name, false);
            return $action
                ? '$dispatch(\'' . $action . "', { morphableType: '" . $entity->getMorphClass() . "', morphableId: '" . $entity->id . "'});"
                : "applyTransition('" . $name . "')";
        };
    @endphp

    {{-- In a table row (no history) the embed stays compact: buttons only, no heading, no diagram --}}
    @if($showHistory && ($transitions->count() || $blocked->count()))
        <div class="small text-body-secondary text-uppercase mb-2">{{ __('Next step') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-start gap-2">
        @foreach($forward as $transition)
            <x-rpd::button
                size="sm"
                :label="$labelOf($transition->getName())"
                :color="$entity->workflow_metadata('class', $transition->getName(), false) ?: 'primary'"
                click="{!! $clickOf($transition->getName()) !!}"
            />
        @endforeach

        @foreach($blocked as $name => $messages)
            @php $action = $entity->workflow_metadata('action', $name, false); @endphp
            <div class="text-center">
                <x-rpd::button
                    size="sm"
                    :label="$labelOf($name)"
                    color="{{ $action ? 'outline-secondary' : 'outline-secondary disabled' }}"
                    click="{!! $action ? $clickOf($name) : null !!}"
                />
                @if(! $action)
                    @foreach($messages as $message)
                        <div class="small text-warning">{{ $message }}</div>
                    @endforeach
                @endif
            </div>
        @endforeach

        @if($aborts->count())
            {{-- next to the others, a step apart: not at the far edge --}}
            <div class="d-flex gap-2 ms-3">
                @foreach($aborts as $transition)
                    <x-rpd::button
                        size="sm"
                        :label="$labelOf($transition->getName())"
                        color="outline-danger"
                        click="{!! $clickOf($transition->getName()) !!}"
                        :confirm="$entity->workflow_metadata('action', $transition->getName(), false) ? false : __('This cannot be undone. Continue?')"
                    />
                @endforeach
            </div>
        @endif
    </div>

    {{-- The process: a state diagram drawn by Mermaid, fetched the first time it is opened; redrawn when the state changes --}}
    @if($showHistory)
        <div class="mt-2 small">
            <a href="#" wire:click.prevent="openDiagram" class="text-decoration-none">
                <i class="fas fa-project-diagram me-1"></i>{{ __('Show process') }}
            </a>
        </div>

        {{-- a wide modal: a state machine is drawn left to right and needs room; the SVG keeps its size, the body scrolls --}}
        <x-rpd::modal name="process-{{ $entity->getKey() }}" title="{{ __('Process') }}: {{ ucfirst(str_replace('_', ' ', $workflowName)) }}" width="lg" :nobuttons="true">
            @if($diagram)
                <div wire:key="diagram-{{ md5($diagram) }}"
                     x-data="{
                        async draw() {
                            if (! window.mermaid) {
                                await new Promise((ok, ko) => { const s = document.createElement('script'); s.src = '{{ \App\Modules\Workflow\Livewire\WorkflowTableEmbed::MERMAID_URL }}'; s.onload = ok; s.onerror = ko; document.head.appendChild(s); });
                            }
                            // compact: small type, little space between the states (a process of ten steps fits a screen)
                            window.mermaid.initialize({
                                startOnLoad: false,
                                theme: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'neutral',
                                themeVariables: { fontSize: '11px' },
                            });
                            const { svg } = await window.mermaid.render('wf' + Math.random().toString(36).slice(2), this.$refs.src.textContent);
                            this.$refs.out.innerHTML = svg;
                            // fit the screen: the whole process at a glance, no scrolling (the SVG keeps its proportions)
                            const el = this.$refs.out.querySelector('svg');
                            if (el) { el.style.cssText = 'max-height: 70vh; max-width: 100%; width: auto; height: auto;'; }
                        }
                     }" x-init="draw()">
                    <pre x-ref="src" class="d-none">{{ $diagram }}</pre>
                    <div x-ref="out" class="overflow-auto text-center"></div>
                </div>
            @endif
        </x-rpd::modal>
    @endif

    @if($showHistory)
        <table class="table table-sm small text-body-secondary mt-3 mb-0">
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
    @endif
</div>
