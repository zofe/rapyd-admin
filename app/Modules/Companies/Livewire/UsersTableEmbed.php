<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class UsersTableEmbed extends Component
{
    use Authorize;

    public Company $company;
    public $users;
    public bool $editable = false;

    public function booted(): void
    {
        $this->authorize('admin|edit company users');
    }

    public function mount(string $companyId, bool $editable = false): void
    {
        $this->company  = Company::findOrFail($companyId);
        $this->editable = $editable;
        $this->refreshUsers();
    }

    #[On('savedUser')]
    public function refreshUsers(): void
    {
        $this->users = $this->company->users()->get();
    }

    public function render()
    {
        return view('companies::users_table_embed', ['users' => $this->users]);
    }
}
