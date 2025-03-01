<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Traits\Authorize;
use Zofe\Rapyd\Traits\WithDataTable;
use Livewire\Component;

class PermissionsTable extends Component
{
    use Authorize;
    use WithDataTable;
    public $search;


    public function booted()
    {
        $this->authorize('admin|edit users');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getDataSet()
    {
        $search = $this->search;
        $items = Role::with('permissions')->where(function ($q) use ($search) {
            $q->where('name', 'like', '%'.$search.'%')
                ->orWhereHas('permissions', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');
                });
        })->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);

        return $items;
    }

    public function render()
    {
        $items = $this->getDataSet();

        return view('auth::permissions_table', compact('items'))
            ->layout('auth::admin');
    }
}
