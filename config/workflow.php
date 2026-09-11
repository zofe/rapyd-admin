<?php

return [
    'enabled' => env('RAPYD_WORKFLOW_ENABLED', true),

    /*
     | Extra files returning state machine definitions, besides the workflow.php
     | found in app/Modules/* and vendor/zofe/*.
     */
    'definition_files' => [],
];
