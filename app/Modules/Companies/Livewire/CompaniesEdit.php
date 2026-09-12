<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompaniesEdit extends Component
{
    use Authorize;

    public Company $company;

    protected $rules = [
        'company.business_name' => 'required',
        'company.email'         => 'nullable|email',
        'company.vat'           => 'nullable',
        'company.phone'         => 'nullable',
        'company.tier'          => 'nullable',
        'company.status'        => 'nullable',
        'company.note'          => 'nullable',
    ];

    public function booted(): void
    {
        $this->authorize('admin|edit companies|edit own business', $this->company->exists ? $this->company : null);
    }

    public function mount(?Company $company = null): void
    {
        $this->company = $company ?? new Company();
    }

    public function save()
    {
        if ($this->company->exists) {
            $this->rules['company.email'] = 'nullable|email|unique:companies,email,' . $this->company->id;
        }

        $this->validate();
        $this->company->save();

        return redirect()->to(route_lang('companies.view', $this->company->id));
    }

    public function render()
    {
        return view('companies::companies_edit')->layout('layout::admin');
    }
}
