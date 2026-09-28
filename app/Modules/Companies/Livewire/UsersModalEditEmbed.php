<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

/**
 * The user modal of the company page. Every entry point takes ids from the browser,
 * so each one resolves them again through the caller: a super admin may administer
 * anyone, the owner of a company the members of that company, anybody else only
 * himself. The permission checked in booted() says the caller may edit *some* user,
 * never which one.
 */
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

    /** Super admins bypass the checks below: they administer every company. */
    protected function isSuperAdmin(): bool
    {
        return auth()->user()->hasAnyRole(config('rapyd.auth.super_admin_roles', ['admin']));
    }

    protected function isOwner(): bool
    {
        return auth()->user()->company_role === 'owner';
    }

    /** The roles a membership can have (config rapyd.companies.roles). */
    protected function companyRoles(): array
    {
        return array_keys(config('rapyd.companies.roles', ['owner' => 'Owner', 'member' => 'Member']));
    }

    /**
     * The account this id names, if the caller may administer it: a super admin any,
     * the owner of a company the users of his own company, anybody else only himself.
     * Anything else is a 403 — users are not scoped by a global scope, so the company
     * is part of the query.
     */
    protected function targetUser($userId)
    {
        $userModel = config('auth.providers.users.model');
        if ($this->isSuperAdmin()) {
            return $userModel::findOrFail($userId);
        }

        $me = auth()->user();
        abort_unless($me->company_id, 403);

        // 403 whether the account is of another tenant or does not exist: no probing
        $target = $userModel::where('company_id', $me->company_id)->find($userId);
        abort_unless($target && ($this->isOwner() || (string) $me->getKey() === (string) $target->getKey()), 403);

        return $target;
    }

    /** The company a caller may put a user in: his own, unless he is a super admin. */
    protected function targetCompany(?string $companyId): ?Company
    {
        if (! $this->isSuperAdmin()) {
            $companyId = auth()->user()->company_id;   // whatever the browser sent
        }

        return $companyId ? Company::withoutGlobalScopes()->findOrFail($companyId) : null;
    }

    #[On('editUser')]
    public function editUser(?string $userId = null, ?string $companyId = null): void
    {
        $userModel        = config('auth.providers.users.model');
        $this->user       = $userId ? $this->targetUser($userId) : new $userModel();
        // creating an account is for owners and super admins
        abort_unless($this->user->exists || $this->isSuperAdmin() || $this->isOwner(), 403);

        $this->company_id = $this->targetCompany($companyId)?->getKey();
        $this->passwd     = '';
        $this->role       = $this->user->company_role ?: 'member';

        $this->dispatch('show-modal', ['editUser']);
    }

    public function save(): void
    {
        // the ids travelled through the browser: resolve them again
        if ($this->user->exists) {
            $this->targetUser($this->user->getKey());
        } else {
            abort_unless($this->isSuperAdmin() || $this->isOwner(), 403);
        }
        // only an owner (or a super admin) hands out the owner role
        abort_if($this->role === 'owner' && ! $this->isSuperAdmin() && ! $this->isOwner(), 403);
        $this->company_id = $this->targetCompany($this->company_id)?->getKey();

        $this->rules['role'] = 'required|in:' . implode(',', $this->companyRoles());
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
        if ($this->company_id && ($isNew || (string) $this->user->company_id === (string) $this->company_id)) {
            $this->user->assignToCompany(Company::withoutGlobalScopes()->findOrFail($this->company_id), $this->role);
        }
        $this->company_id = null;

        $this->dispatch('hide-modals');
        $this->dispatch('savedUser');
    }

    #[On('deleteUser')]
    public function deleteUser(string $userId): void
    {
        $target = $this->targetUser($userId);
        // the owner of a company is not removed from the company page, and nobody deletes himself
        abort_if($target->company_role === 'owner' && ! $this->isSuperAdmin(), 403);
        abort_if((string) $target->getKey() === (string) auth()->user()->getKey(), 403);

        $target->delete();
        $this->dispatch('savedUser');
    }

    public function render()
    {
        $availableRoles = config('rapyd.companies.roles', ['owner' => 'Owner', 'member' => 'Member']);

        return view('companies::users_modal_edit_embed', compact('availableRoles'));
    }
}
