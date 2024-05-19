<?php

namespace Zofe\Rapyd\Http\Livewire;

use Livewire\Component;

class RapydApp extends Component
{
    protected $listeners = ['sidebar-toggle' => 'sidebarToggle'];

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
