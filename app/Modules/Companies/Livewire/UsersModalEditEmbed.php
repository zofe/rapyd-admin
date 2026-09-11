<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

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

        if ($userId && $this->company_id) {
            $pivot = $this->user->companies()->where('companies.id', $this->company_id)->first()?->pivot;
            $this->role = $pivot?->role ?? 'member';
        } else {
            $this->role = 'member';
        }

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

        if ($this->company_id) {
            $this->user->companies()->syncWithoutDetaching([
                $this->company_id => ['role' => $this->role, 'is_primary' => true],
            ]);
            if ($isNew) {
                $this->user->company_id = $this->company_id;
                $this->user->save();
            }
            $this->company_id = null;
        }

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
