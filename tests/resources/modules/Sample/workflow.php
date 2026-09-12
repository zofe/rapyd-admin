<?php

return [
    'sample_ticket' => [
        'type' => 'state_machine',
        'marking_store' => ['type' => 'single_state', 'property' => 'status'],
        'supports' => ['App\Modules\Sample\Models\Ticket'],
        'places' => ['open', 'closed'],
        'transitions' => ['close' => ['from' => 'open', 'to' => 'closed']],
    ],
];
