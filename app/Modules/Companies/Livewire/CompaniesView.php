<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompaniesView extends Component
{
    use Authorize;

    public Company $company;

    public function booted(): void
    {
        $this->authorize('admin|view companies|edit companies|view own business|edit own business', $this->company);
    }

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    public function render()
    {
        return view('companies::companies_view', ['company' => $this->company])
            ->layout('companies::admin');
    }
}
