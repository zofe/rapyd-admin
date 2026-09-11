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

    // Parameter names are kept as the shop views pass them.
    public function mount(string $workfloableType, string $workfloableId, bool $editable = false, bool $showHistory = false): void
    {
        $modelClass = Relation::getMorphedModel($workfloableType) ?? $workfloableType;
        abort_unless(class_exists($modelClass), 404);

        $this->entity = $modelClass::findOrFail($workfloableId);
        $this->editable = $editable;
        $this->showHistory = $showHistory;
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

    public function render()
    {
        return view('workflow::workflow_table_embed');
    }
}
