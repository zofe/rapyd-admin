<?php

namespace Zofe\Rapyd\Http\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class RapydApp extends Component
{
    #[On('sidebar-toggle')]
    public function sidebarToggle()
    {
        dd('qui');
        session()->put('sidebar-show', ! session()->get('sidebar-show'));
    }

    public function render()
    {
        return view('rpd::app');
    }
}
