<?php

return [
    'legacy_model_binding' => true,

    // Layout of full-page components that do not call ->layout() in render():
    // Livewire 4 reads component_layout, 'layout' is kept for the old key.
    'component_layout' => 'layout::frontend',
    'layout' => 'layout::frontend',

    'pagination_theme' => 'pagination',

];
