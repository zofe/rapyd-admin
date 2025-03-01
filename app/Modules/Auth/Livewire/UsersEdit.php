<?php

namespace App\Modules\Auth\Livewire;



use App\Models\User;
use App\Modules\Auth\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use App\Modules\Auth\Traits\Authorize;


class UsersEdit extends Component
{
    use Authorize;

    public $user;

    public $psswd;

    public $roles = [];

    public $unchangable = [1, 2, 3, 4, 5, 6, 7];

    public $fixed_type;

    public $readonly = false;

    public $available_roles = [];
    public $company_roles = [];


    protected $rules = [
        'user.name' => 'required',
        'user.email' => 'required|email|indisposable|unique:users,email',
        'psswd' => 'nullable',
        'roles' => 'nullable',
    ];

    public function booted()
    {
        $this->authorize('admin|edit users');
    }

    public function addRule($field, $rule)
    {
        $this->rules[$field] = $rule;
    }

    public function mount(?User $user)
    {
        $this->user = $user;
        if($user->exists && method_exists($user, 'roles')) {
            $this->roles = $user->roles->pluck('id')->toArray();
        }
        $this->available_roles = Role::query()->pluck('name', 'id')->toArray();
    }

    public function save()
    {
        if(!$this->user->exists)
        {
            $this->addRule('psswd', 'required|min:8');
        } else {
            $this->addRule('user.email', 'required|email|indisposable|unique:users,email,'.$this->user->id);
        }

        $this->validate();

        if($this->psswd)
        {
            $this->user->password = Hash::make($this->psswd);
        }

        $this->user->save();
        if(method_exists($this->user, 'roles')) {
            $this->user->roles()->sync(array_filter($this->roles));
        }


        return redirect()->to(route_lang('auth.users.view', $this->user->getKey()));
    }

    public function render()
    {
        return view('auth::users_edit')
            ->layout('auth::admin');
    }
}
