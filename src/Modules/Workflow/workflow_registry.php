<?php

return [

    /**
     * When set to true, the registry will track the workflows that have been loaded.
     * This is useful when you're loading from a DB, or just loading outside of the
     * main config files.
     */
    'track_loaded' => true,

    /**
     * Only used when track_loaded = true
     *
     * When set to true, a registering a duplicate workflow will be ignored (will not load the new definition)
     * When set to false, a duplicate workflow will throw a DuplicateWorkflowException
     */
    'ignore_duplicates' => true,

];
