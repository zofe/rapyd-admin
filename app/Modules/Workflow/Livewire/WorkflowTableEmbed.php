<?php

namespace App\Modules\Workflow\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Workflow\Models\WorkflowStep;

class WorkflowTableEmbed extends Component
{
    use Authorize;

    public $entity;

    public bool $editable = false;

    public bool $showHistory = false;

    public $steps;

    public string $workflowName = '';

    public array $blockers = [];

    public array $marking = [];

    /** "Show process": the state diagram of the workflow, drawn in the browser by Mermaid (loaded on demand). */
    public bool $showDiagram = false;

    /** The "Show process" link under the buttons; false when the page has its own (see showProcess()). */
    public bool $processLink = true;

    public const MERMAID_URL = 'https://cdnjs.cloudflare.com/ajax/libs/mermaid/12.1.0/mermaid.min.js';

    // Parameter names are kept as the shop views pass them.
    public function mount(string $workfloableType, string $workfloableId, bool $editable = false, bool $showHistory = false, bool $processLink = true): void
    {
        $modelClass = Relation::getMorphedModel($workfloableType) ?? $workfloableType;
        abort_unless(class_exists($modelClass), 404);

        $this->entity = $modelClass::findOrFail($workfloableId);
        $this->editable = $editable;
        $this->showHistory = $showHistory;
        $this->processLink = $processLink;
        $this->workflowName = $workfloableType;

        $this->authorize('admin|view everything|edit everything|view workflow|edit workflow', $this->entity);
        $this->refreshWorkflowSteps();
    }

    #[On('savedStep')]
    public function refreshWorkflowSteps(): void
    {
        // Older rows stored the FQCN, newer ones the morph alias.
        $this->steps = WorkflowStep::whereIn('workflowable_type', array_unique([$this->entity->getMorphClass(), get_class($this->entity)]))
            ->where('workflowable_id', $this->entity->getKey())
            ->orderByDesc('created_at')
            ->get();

        $this->marking = array_keys(array_filter(
            \Workflow::get($this->entity, $this->workflowName)->getMarking($this->entity)->getPlaces()
        ));
        $this->blockers = method_exists($this->entity, 'getWorkflowBlockers')
            ? $this->entity->getWorkflowBlockers($this->workflowName)
            : [];
    }

    public function applyTransition(string $transition): void
    {
        if (! $this->editable || ! empty($this->blockers)) {
            return;
        }
        $this->authorize('admin|edit everything|edit workflow', $this->entity);

        $workflow = \Workflow::get($this->entity, $this->workflowName);
        $fromPlaces = $workflow->getMarking($this->entity)->getPlaces();
        $workflow->apply($this->entity, $transition);
        $this->entity->save();

        WorkflowStep::create([
            'user_id'           => auth()->id(),
            'company_id'        => auth()->user()->company_id ?? null,
            'workflowable_type' => $this->entity->getMorphClass(),
            'workflowable_id'   => $this->entity->getKey(),
            'places'            => $workflow->getMarking($this->entity)->getPlaces(),
            'places_from'       => $fromPlaces,
            'last_transition'   => $transition,
            'transition_date'   => now(),
        ]);

        $this->refreshWorkflowSteps();
        $this->dispatch('refresh');
    }

    /** "Show process": the diagram is rendered from now on (and redrawn at every state change) and its modal opens. */
    public function openDiagram(): void
    {
        $this->showDiagram = true;
        $this->dispatch('show-modal', ['process-' . $this->entity->getKey()]);
    }

    /**
     * The same, asked from outside: a page puts its own "Show process" link where it likes (the header of a card)
     * and dispatches `show-process` with the type and the id. Only the embed with the history answers.
     */
    #[On('show-process')]
    public function showProcess(?string $type = null, ?string $id = null): void
    {
        if (! $this->showHistory || ($type && $type !== $this->workflowName) || ($id && (string) $id !== (string) $this->entity->getKey())) {
            return;
        }
        $this->openDiagram();
    }

    /**
     * The workflow as a Mermaid state diagram: every place with its label, every transition as an arrow,
     * the entry and the final places, and the current place(s) marked. Built from the definition, so it is
     * true for any state machine declared in a workflow.php.
     */
    public function diagram(): string
    {
        $definition = \Workflow::get($this->entity, $this->workflowName)->getDefinition();
        $store = $definition->getMetadataStore();
        $text = fn (?string $label, string $fallback) => ucfirst(str_replace([':', '"', "\n", '_'], ' ', $label ?: $fallback));

        // top to bottom: a process reads like a list, and a tall drawing scrolls where a wide one shrinks
        $lines = ['stateDiagram-v2', '    direction TB'];
        foreach ($definition->getPlaces() as $place) {
            $lines[] = "    {$place}: " . $text($store->getPlaceMetadata($place)['label'] ?? null, $place);
        }
        foreach ($definition->getInitialPlaces() as $place) {
            $lines[] = "    [*] --> {$place}";
        }
        foreach ($definition->getTransitions() as $transition) {
            $label = $text($store->getTransitionMetadata($transition)['label'] ?? null, $transition->getName());
            foreach ($transition->getFroms() as $from) {
                foreach ($transition->getTos() as $to) {
                    $lines[] = "    {$from} --> {$to}: {$label}";
                }
            }
        }
        foreach ($definition->getPlaces() as $place) {
            if ($store->getPlaceMetadata($place)['final'] ?? false) {
                $lines[] = "    {$place} --> [*]";
            }
        }
        $lines[] = '    classDef current fill:#0d6efd,stroke:#0d6efd,color:#fff';
        foreach ($this->marking as $place) {
            $lines[] = "    class {$place} current";
        }

        return implode("\n", $lines);
    }

    public function render()
    {
        return view('workflow::workflow_table_embed', [
            'diagram' => $this->showDiagram ? $this->diagram() : null,
        ]);
    }
}
