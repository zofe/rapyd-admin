<?php

namespace App\Modules\Auth\Livewire;

use App\Models\User;
use App\Modules\Auth\Traits\Authorize;
use Zofe\Rapyd\Traits\WithDataTable;
use Livewire\Component;

class UsersTable extends Component
{
    use Authorize;
    use WithDataTable;
    public $search;

    public function booted()
    {
       $this->authorize('admin|view everything|edit everything|view users|edit users');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getDataSet()
    {
        $items = User::where('name','like','%'.$this->search.'%')
            ->orWhere('email','like','%'.$this->search.'%');

        return $items
            ->orderBy($this->sortField,$this->sortAsc ?'asc':'desc')
            ->paginate($this->perPage)
            ;
    }

    public function render()
    {
        $items = $this->getDataSet();

        return view('auth::users_table', compact('items'))
            ->layout('auth::admin');
    }
}
