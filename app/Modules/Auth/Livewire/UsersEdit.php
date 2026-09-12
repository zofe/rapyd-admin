<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Models\Role;
use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class UsersEdit extends Component
{
    use Authorize;
    use Limit;

    public $user;

    public $psswd;

    public $roles = [];

    public $unchangable = [1, 2, 3, 4, 5, 6, 7];

    public $fixed_type;

    public $readonly = false;

    public $available_roles = [];

    protected $rules = [
        'user.name' => 'required',
        'user.email' => 'required|email|indisposable|unique:users,email',
        'psswd' => 'nullable',
        'roles' => 'nullable',
        'user.company_id' => 'nullable',
        'user.company_role' => 'nullable',
    ];

    public function booted()
    {
        $this->authorize('admin|edit users');
        $this->limit();
    }

    public function addRule($field, $rule)
    {
        $this->rules[$field] = $rule;
    }

    public function mount($user = null)
    {
        $userModel = config('auth.providers.users.model');
        $this->user = $user instanceof Model ? $user : new $userModel();

        if ($this->user->exists && method_exists($this->user, 'roles')) {
            $this->roles = $this->user->roles->pluck('id')->toArray();
        }
        $this->available_roles = Role::query()->pluck('name', 'id')->toArray();

        if ($this->hasCompanies() && ! $this->user->company_role) {
            $this->user->company_role = 'member';
        }
    }

    public function hasCompanies(): bool
    {
        return config('rapyd.companies.enabled') && method_exists($this->user, 'company');
    }

    /**
     * The company is chosen when the user is created; afterwards only a super
     * admin can move him to another one.
     */
    public function canEditCompany(): bool
    {
        if (! $this->hasCompanies()) {
            return false;
        }

        return ! $this->user->exists
            || auth()->user()->hasAnyRole(config('rapyd.auth.super_admin_roles', ['admin']));
    }

    public function save()
    {
        if (! $this->user->exists) {
            $this->addRule('psswd', 'required|min:8');
        } else {
            $this->addRule('user.email', 'required|email|indisposable|unique:users,email,'.$this->user->id);
        }

        if ($this->canEditCompany()) {
            // Company::find() runs under the Limit scope: a non-admin can only
            // pick a company he is allowed to see.
            $this->addRule('user.company_id', ['nullable', fn ($attr, $value, $fail) => $value && ! Company::find($value) && $fail('Invalid company.')]);
            $this->addRule('user.company_role', 'required_with:user.company_id|in:'.implode(',', array_keys($this->companyRoles())));
        }

        $this->validate();

        if ($this->psswd) {
            $this->user->password = Hash::make($this->psswd);
        }

        if ($this->hasCompanies()) {
            if (! $this->canEditCompany()) {
                // Whatever came in with the request, keep the stored membership.
                $this->user->company_id = $this->user->getOriginal('company_id');
                $this->user->company_role = $this->user->getOriginal('company_role');
            } elseif (! $this->user->company_id) {
                $this->user->company_id = null;
                $this->user->company_role = null;
            }
        }

        $this->user->save();
        if (method_exists($this->user, 'roles')) {
            $this->user->roles()->sync(array_filter($this->roles));
        }

        return redirect()->to(route_lang('auth.users.view', $this->user->getKey()));
    }

    protected function companyRoles(): array
    {
        return config('rapyd.companies.roles', ['owner' => 'Owner', 'member' => 'Member']);
    }

    public function render()
    {
        $availableCompanies = $this->canEditCompany()
            ? Company::query()->orderBy('business_name')->pluck('business_name', 'id')->toArray()
            : [];

        // Read-only label: shown regardless of what the Limit lets the viewer see.
        $currentCompany = ($this->hasCompanies() && $this->user->company_id)
            ? Company::withoutGlobalScopes()->find($this->user->company_id)
            : null;

        return view('auth::users_edit', [
            'availableCompanies' => $availableCompanies,
            'currentCompany' => $currentCompany,
            'companyRoles' => $this->companyRoles(),
        ])->layout('layout::admin');
    }
}
