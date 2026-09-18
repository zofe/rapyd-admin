<?php

/*
| A module's workflow.php: state machines picked up by rapyd-admin (app/Modules/star/workflow.php and
| vendor/zofe/star/workflow.php) and merged into config('workflow'), read by zerodahero/laravel-workflow.
|
| THE NAME OF A WORKFLOW IS THE MORPH ALIAS OF ITS MODEL ('ticket' → Relation::morphMap(['ticket' => Ticket::class])):
| the workflow embed uses one value (workfloableType) for both. Register the alias in the module's service provider
| (a package module) or in AppServiceProvider (an app module).
*/

use App\Modules\Support\Models\Ticket;

return [
    'ticket' => [
        'type'            => 'state_machine',
        'marking_store'   => ['type' => 'single_state', 'property' => 'status'],   // the column holding the state
        'initial_marking' => 'open',
        'supports'        => [Ticket::class],

        'places' => [
            'open'     => ['metadata' => ['label' => 'open']],
            'assigned' => ['metadata' => ['label' => 'assigned']],
            'resolved' => ['metadata' => ['label' => 'resolved']],
            'closed'   => ['metadata' => ['label' => 'closed', 'final' => true]],   // final: nothing more to do
        ],

        'transitions' => [
            // a plain transition: the embed shows a button, applyTransition() runs it
            'resolve' => ['from' => ['assigned'], 'to' => 'resolved', 'metadata' => ['label' => 'resolve']],

            // a transition that needs input (an operator, a note…): 'action' names a Livewire event the page
            // handles with a modal; the embed dispatches it instead of applying the transition itself
            'assign'  => ['from' => ['open'], 'to' => 'assigned', 'metadata' => ['label' => 'assign', 'action' => 'assignTicket']],

            'close'   => ['from' => ['open', 'assigned', 'resolved'], 'to' => 'closed', 'metadata' => ['label' => 'close', 'class' => 'danger']],
            'reopen'  => ['from' => ['resolved', 'closed'], 'to' => 'open', 'metadata' => ['label' => 'reopen', 'class' => 'warning']],
        ],
    ],
];
