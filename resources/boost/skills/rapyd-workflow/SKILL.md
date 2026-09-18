---
name: rapyd-workflow
description: Design and implement a state machine (workflow) on a Rapyd Admin model: places, transitions, guards and effects as listeners, transitions with a modal, the workflow embed on the page, tests. Use it whenever a model has a lifecycle (orders, tickets, requests, approvals).
---

# Rapyd Admin workflows

## When to use this skill

Whenever a model has a lifecycle: an order that is paid, processed and shipped; a ticket that is assigned, resolved
and closed; a request that is approved or rejected. The temptation is a `status` column changed by hand in a dozen
places. In Rapyd Admin the lifecycle is a **state machine** declared once, applied through transitions, with the rules
(guards) and the side effects (listeners) in one class, and a ready-made page block that shows the buttons and the
history. Most modules built on Rapyd Admin are built around this.

Under the hood: `zerodahero/laravel-workflow` (Symfony Workflow). Rapyd Admin adds the discovery of the definitions,
the `WorkflowTrait`, the `WorkflowStep` history and the `workflow::workflow-table-embed` component.

## The pieces

| Piece | Where | What |
|---|---|---|
| definition | `app/Modules/{Name}/workflow.php` | places, transitions, metadata (`references/workflow.php`) |
| model | `use WorkflowTrait;` + the `status` column (the `marking_store` property) | `workflow_apply()`, `workflow_can()`, `workflow_transitions()`, `workflow_transition_blocked()`, `workflow_metadata()` |
| morph alias | `Relation::morphMap(['ticket' => Ticket::class])` | **the workflow name and the morph alias must be the same string**: the embed uses one value for both |
| subscriber | `app/Modules/{Name}/Listeners/{Model}WorkflowSubscriber.php` | guards and effects (`references/TicketWorkflowSubscriber.php`), registered with `Event::subscribe()` |
| page block | `<livewire:workflow::workflow-table-embed workfloableType="ticket" :workfloableId="$ticket->id" :editable="true" :showHistory="true" />` | the buttons of the available transitions (disabled with the reason when a guard blocks them), the history of `WorkflowStep` |
| modal | a `*Modal` Livewire component + `<x-rpd::modal>` (`references/TicketsAssignModal.php`) | a transition that needs input: `'action' => 'eventName'` in the transition metadata |
| history | `Zofe\Rapyd\Modules\Workflow\Models\WorkflowStep` | who, when, from which places, with which `meta`; written by the embed, or by your code when you apply a transition yourself |

## Procedure

### 1. Design on paper first

Write the places and the transitions as a list before touching code; show it to the user when the lifecycle is not
obvious. Rules of thumb:
- A place is a state the model **stays** in (`open`, `assigned`); a transition is what **moves** it (`assign`). Name
  places with adjectives / past participles, transitions with verbs.
- Mark terminal places `'final' => true`: pages use it (`workflow_metadata('final', $model->status)`).
- One transition per user intention, not one per field change. Data that changes without changing the state is not a transition.
- Who triggers it: the operator (a button), the customer (a page), the system (a webhook, a command)? A transition
  that needs input from a person gets an `action` and a modal; one that is automatic is applied by code.
- What must be true before (guards) and what happens after (effects). Keep both in the subscriber, not in the pages.

### 2. Declare

`workflow.php` in the module (see `references/workflow.php`): `marking_store.property` is the column, `initial_marking`
the state of a new record, `supports` the model class. Metadata per transition: `label` (the button), `class` (the
button colour: `primary`, `warning`, `danger`…), `action` (the Livewire event of a modal). The file is loaded
automatically from `app/Modules/*/workflow.php` and from installed `vendor/zofe/*` packages.

Register the morph alias with the same name as the workflow: in a package, `Relation::morphMap([...], true)` in the
service provider's `register()`; in an app module, in `AppServiceProvider::boot()`.

### 3. Rules and effects: the subscriber

`references/TicketWorkflowSubscriber.php`. Events are named `workflow.{name}.{guard|transition|completed}.{transition}`:
- `guard`: `$event->setBlocked(true, 'reason')`. A non-empty reason shows the button disabled with the message; an
  empty reason hides the button (a transition that does not apply here).
- `transition`: runs **inside** the transition. Throw to abort: the model keeps its state. Use it when an external
  call must succeed for the state to change (a gateway, an API, a provisioning driver).
- `completed`: the marking changed, the model is **not saved yet**: set attributes, open records, send mails; the
  caller saves. Do not call `save()` on the subject inside a listener unless you own the whole flow.

Guards that depend on other models (all the lines assigned, the payment confirmed…) belong here too, so every page
and every command gets the same answer.

### 4. Apply

- From a page, through the embed: nothing to write. `editable` shows the buttons, `showHistory` the steps.
- From your code: `$model->workflow_apply('transition', 'name'); $model->save();` and, when it matters for the history,
  create the `WorkflowStep` (see `references/TicketsAssignModal.php`). Check first with `workflow_can()`; the reasons
  are in `workflow_transition_blocked()`.
- With input: the `action` metadata + a modal component that listens to the event (`#[On('assignTicket')]`), sets the
  data on the model **before** `apply()` (guards may read it), applies, saves, records the step, dispatches
  `hide-modals`, `savedStep` and `refresh`.

### 5. Show

On the detail page, a card with the state and the embed:

```blade
<x-rpd::card title="Status">
    <dl class="row">
        <dt class="col-4">Status</dt><dd class="col-8">{{ $ticket->status }}</dd>
    </dl>
    <livewire:workflow::workflow-table-embed workfloableType="ticket" workfloableId="{{ $ticket->id }}" :editable="true" :showHistory="true" />
</x-rpd::card>
```

The page component listens to `refresh` (`#[On('refresh')] public function refresh() { $this->ticket->refresh(); }`)
so the rest of the page follows the state. The embed requires the `view workflow` / `edit workflow` permissions
(admins have everything): give them to the roles that operate the workflow in the module's `config.php`.

Lists show the state as a badge and, when useful, the blocked reasons (`workflow_transition_blocked()`); a parent
model can count what is still open in its children with `workflow_count_incomplete_from($children)`.

### 6. Test

`references/WorkflowTest.php`: the transitions (`workflow_can`, `workflow_apply`), the guards (the blocked reasons),
the effects (what the listener changed), the embed (`Livewire::test('workflow::workflow-table-embed', [...])`), the
permissions (`assertForbidden()`). Then open the page: press the buttons in order, check the history.

## Do not

- Do not assign `status` by hand, anywhere. If a state change has no transition, add the transition.
- Do not put the rules in the Livewire components: a guard is asked by every page and every command.
- Do not name the workflow differently from the model's morph alias.
- Do not skip `save()` after `workflow_apply()`: the transition changes the attribute, the caller persists it.
- Do not reuse a transition for two different intentions because they share the same target place.
