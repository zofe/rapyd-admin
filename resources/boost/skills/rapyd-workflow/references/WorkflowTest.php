<?php

namespace App\Modules\Support\Tests;

use App\Modules\Support\Models\Ticket;
use Livewire\Livewire;
use Tests\TestCase;
use Zofe\Rapyd\Modules\Workflow\Models\WorkflowStep;

/** What to test in a workflow: the transitions, the guards, the effects, the embed. */
class TicketWorkflowTest extends TestCase
{
    public function test_a_ticket_is_resolved_only_with_a_resolution()
    {
        $ticket = Ticket::create(['subject' => 'Printer on fire', 'status' => 'assigned']);

        $this->assertFalse($ticket->workflow_can('resolve', 'ticket'), 'no resolution, no transition');
        $this->assertSame(['resolve' => ['write the resolution first']], $ticket->workflow_transition_blocked('ticket'));

        $ticket->resolution = 'Replaced the fuser';
        $ticket->workflow_apply('resolve', 'ticket');
        $ticket->save();
        $this->assertSame('resolved', $ticket->fresh()->status);
    }

    public function test_closing_records_the_step_and_the_effect()
    {
        $this->actingAs($this->admin());
        $ticket = Ticket::create(['subject' => 'x', 'status' => 'resolved']);

        Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $ticket->id, 'editable' => true])
            ->assertSee('close')
            ->call('applyTransition', 'close')
            ->assertDispatched('refresh');

        $this->assertSame('closed', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->closed_at, 'the completed listener ran');
        $this->assertSame('close', WorkflowStep::where('workflowable_id', $ticket->id)->latest()->first()->last_transition);
    }
}
