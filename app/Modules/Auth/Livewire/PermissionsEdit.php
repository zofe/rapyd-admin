<?php

namespace App\Modules\Auth\Livewire;



use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Livewire\Component;
use App\Modules\Auth\Traits\Authorize;


class PermissionsEdit extends Component
{
    use Authorize;
    public $role;

    public $roles = [];
    public $permissions = [];

    public $readonly = false;

    public $available_permissions = [];

    protected $rules = [
        'permissions' => 'nullable',
    ];

    public function booted()
    {
        $this->authorize('admin|edit users');
    }

    public function addRule($field, $rule)
    {
        $this->rules[$field] = $rule;
    }

    public function mount(?Role $role)
    {
        $this->role = $role;
        if($role->exists) {
            $this->permissions = $this->role->permissions->pluck('id')->toArray();
        }
        $this->available_permissions = Permission::query()->pluck('name', 'id')->toArray();

    }

    public function save()
    {
        if(!$this->role->exists)
        {

        } else {

        }

        $this->validate();

        $this->role->permissions()->sync(array_filter($this->permissions));
        $this->role->save();

        return redirect()->to(route_lang('auth.permissions'));
    }

    public function render()
    {
        return view('auth::permissions_edit')
            ->layout('auth::admin');
    }
}
