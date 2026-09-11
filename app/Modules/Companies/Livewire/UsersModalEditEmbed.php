<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class UsersModalEditEmbed extends Component
{
    use Authorize;

    public $user;
    public string $passwd    = '';
    public ?string $company_id = null;
    public string $role      = 'member';

    protected $rules = [
        'user.name'  => 'required',
        'user.email' => 'required|email',
        'passwd'     => 'nullable|min:6',
        'role'       => 'required|string',
    ];

    public function booted(): void
    {
        $this->authorize('admin|edit users|edit own users');
    }

    #[On('editUser')]
    public function editUser(?string $userId = null, ?string $companyId = null): void
    {
        $userModel        = config('auth.providers.users.model');
        $this->user       = $userId ? ($userModel::find($userId) ?? new $userModel()) : new $userModel();
        $this->company_id = $companyId;
        $this->passwd     = '';
        $this->role       = $this->user->company_role ?: 'member';

        $this->dispatch('show-modal', ['editUser']);
    }

    public function save(): void
    {
        if (! $this->user->exists) {
            $this->rules['passwd'] = 'required|min:8';
        } else {
            $this->rules['user.email'] = 'required|email|unique:users,email,' . $this->user->id;
        }

        $this->validate();

        if ($this->passwd) {
            $this->user->password = Hash::make($this->passwd);
        }

        $isNew = ! $this->user->exists;
        $this->user->save();

        if ($isNew) {
            $this->user->assignRole(config('rapyd.companies.user_role', 'customer'));
        }

        // A new user joins this company; an existing member only changes role here.
        // Moving a user to another company is done from the user page by a super admin.
        if ($this->company_id && ($isNew || $this->user->company_id === $this->company_id)) {
            $this->user->assignToCompany(Company::findOrFail($this->company_id), $this->role);
        }
        $this->company_id = null;

        $this->dispatch('hide-modals');
        $this->dispatch('savedUser');
    }

    #[On('deleteUser')]
    public function deleteUser(string $userId): void
    {
        config('auth.providers.users.model')::findOrFail($userId)->delete();
        $this->dispatch('savedUser');
    }

    public function render()
    {
        $availableRoles = config('rapyd.companies.roles', ['owner' => 'Owner', 'member' => 'Member']);
        return view('companies::users_modal_edit_embed', compact('availableRoles'));
    }
}
