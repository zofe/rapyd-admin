<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;
use Zofe\Rapyd\Traits\WithDataTable;

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

    public function hasCompanies(): bool
    {
        return config('rapyd.companies.enabled')
            && method_exists(config('auth.providers.users.model'), 'company');
    }

    public function getDataSet()
    {
        $userModel = config('auth.providers.users.model');

        $items = $userModel::query()
            ->with('roles')
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('email', 'like', '%'.$this->search.'%');
            });

        if ($this->hasCompanies()) {
            $items->with('company');
        }

        return $items
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $items = $this->getDataSet();

        return view('auth::users_table', compact('items'))
            ->layout('layout::admin');
    }
}
