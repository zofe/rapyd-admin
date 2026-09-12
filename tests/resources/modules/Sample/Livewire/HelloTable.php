<?php

namespace App\Modules\Sample\Livewire;

use Livewire\Component;

class HelloTable extends Component
{
    public function render()
    {
        return view('sample::hello')->layout('layout::admin');
    }
}
