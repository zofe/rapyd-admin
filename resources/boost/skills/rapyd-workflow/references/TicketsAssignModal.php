<?php

namespace App\Modules\Support\Livewire;

use App\Modules\Support\Models\Ticket;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Auth\Traits\Authorize;
use Zofe\Rapyd\Modules\Workflow\Models\WorkflowStep;

/**
 * A transition that needs input: the workflow embed dispatches the event named in the transition's
 * metadata ('action' => 'assignTicket') with { morphableType, morphableId }; this component opens a
 * modal, collects the data, applies the transition itself and records the step.
 * Included once in the page: <livewire:support::tickets-assign-modal /> and, in the view,
 * <x-rpd::modal name="assignTicket" title="Assign" action="doAssign" actionLabel="Assign">…</x-rpd::modal>
 */
class TicketsAssignModal extends Component
{
    use Authorize;

    public ?string $ticketId = null;

    public ?int $assigneeId = null;

    protected $rules = ['assigneeId' => 'required|integer|exists:users,id'];

    public function booted(): void
    {
        $this->authorize('admin|edit tickets');
    }

    #[On('assignTicket')]
    public function openModal($morphableType, $morphableId): void
    {
        $this->ticketId = $morphableId;
        $this->assigneeId = null;
        $this->dispatch('show-modal', ['assignTicket']);
    }

    public function doAssign(): void
    {
        $this->validate();
        $ticket = Ticket::findOrFail($this->ticketId);

        $workflow = \Workflow::get($ticket, 'ticket');
        $fromPlaces = $workflow->getMarking($ticket)->getPlaces();

        $ticket->assignee_id = $this->assigneeId;   // set before apply(): a guard may read it
        $workflow->apply($ticket, 'assign');
        $ticket->save();

        WorkflowStep::create([
            'user_id'           => auth()->id(),
            'company_id'        => auth()->user()->company_id ?? null,
            'workflowable_type' => $ticket->getMorphClass(),
            'workflowable_id'   => $ticket->getKey(),
            'places'            => $workflow->getMarking($ticket)->getPlaces(),
            'places_from'       => $fromPlaces,
            'last_transition'   => 'assign',
            'transition_date'   => now(),
            'meta'              => ['assignee_id' => $this->assigneeId],
        ]);

        $this->dispatch('hide-modals');
        $this->dispatch('savedStep');   // the embed reloads the history
        $this->dispatch('refresh');     // the page reloads the model
    }

    public function render()
    {
        return view('support::tickets_assign_modal');
    }
}
