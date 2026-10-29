<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Workflow\Models\WorkflowStep;
use Zofe\Rapyd\Tests\Models\Ticket;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class WorkflowTest extends TestCase
{
    use DatabaseMigrations;

    protected Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthSeeder::class);
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());
        $this->ticket = Ticket::create(['subject' => 'Printer on fire']);
    }

    public function test_trait_exposes_transitions_and_metadata()
    {
        $this->assertTrue($this->ticket->workflow_can('close', 'ticket'));
        $this->assertEquals(['close', 'discard'], array_map(fn ($t) => $t->getName(), $this->ticket->workflow_transitions('ticket')));
        $this->assertEquals('Close ticket', $this->ticket->workflow_metadata('label', 'close', false, 'ticket'));
    }

    public function test_the_embed_tells_the_next_step_apart_from_the_abort()
    {
        // in a table row (no history) the embed is compact: the buttons, nothing else
        Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id, 'editable' => true])
            ->assertSee('Close ticket')->assertDontSee('Next step')->assertDontSee('Show process');

        $html = Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id, 'editable' => true, 'showHistory' => true])
            ->assertSee('Next step')
            ->assertSee('Close ticket')
            ->assertSee('Discard')
            ->assertSeeHtml('btn btn-outline-danger')
            ->assertSeeHtml('This cannot be undone')
            ->html();

        $this->assertLessThan(strpos($html, 'Discard'), strpos($html, 'Close ticket'), 'the work first, the abort apart');
    }

    public function test_the_diagram_describes_the_workflow_and_marks_the_current_place()
    {
        $component = Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id, 'editable' => true, 'showHistory' => true])
            ->assertSee('Show process')
            ->assertDontSee('stateDiagram-v2');

        $diagram = $component->instance()->diagram();
        foreach (['stateDiagram-v2', '[*] --> open', 'open --> closed: Close ticket', 'open --> discarded: Discard', 'closed --> [*]', 'class open current'] as $line) {
            $this->assertStringContainsString($line, $diagram);
        }
        $this->assertStringNotContainsString('class closed current', $diagram);

        // open: the source is in the page, not fetched by the server; after a transition the current place moves
        // a page with its own link: the embed answers the event for its own record only, and hides the inline link
        Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id, 'showHistory' => true, 'processLink' => false])
            ->assertDontSee('Show process')
            ->dispatch('show-process', type: 'ticket', id: 'another-one')->assertNotDispatched('show-modal')
            ->dispatch('show-process', type: 'ticket', id: $this->ticket->id)->assertDispatched('show-modal');

        $component->call('openDiagram')->assertDispatched('show-modal')->assertSee('class open current')
            ->call('applyTransition', 'close')->assertSee('class closed current')->assertDontSee('class open current');
    }

    public function test_embed_applies_a_transition_and_records_the_step()
    {
        Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id, 'editable' => true, 'showHistory' => true])
            ->assertSee('Close ticket')
            ->call('applyTransition', 'close')
            ->assertDispatched('refresh')
            ->assertSee('closed');

        $this->assertEquals('closed', $this->ticket->fresh()->status);
        $step = WorkflowStep::firstOrFail();
        $this->assertEquals('close', $step->last_transition);
        $this->assertEquals(['open' => 1], $step->places_from);
        $this->assertEquals('ticket', $step->workflowable_type);
    }

    public function test_read_only_embed_does_not_apply_transitions()
    {
        Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id])
            ->call('applyTransition', 'close');

        $this->assertEquals('open', $this->ticket->fresh()->status);
        $this->assertDatabaseCount('workflow_steps', 0);
    }

    public function test_customer_cannot_see_the_workflow()
    {
        $customer = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer);

        Livewire::test('workflow::workflow-table-embed', ['workfloableType' => 'ticket', 'workfloableId' => $this->ticket->id])
            ->assertForbidden();
    }
}
