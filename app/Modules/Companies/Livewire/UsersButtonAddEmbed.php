<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class UsersButtonAddEmbed extends Component
{
    use Authorize;

    public Company $company;

    public function booted(): void
    {
        $this->authorize('admin|edit company users');
    }

    public function mount(string $companyId): void
    {
        $this->company = Company::findOrFail($companyId);
    }

    public function render()
    {
        return view('companies::users_button_add_embed');
    }
}
