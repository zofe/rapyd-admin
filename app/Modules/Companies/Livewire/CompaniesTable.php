<?php

namespace App\Modules\Companies\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use Livewire\Component;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Traits\WithDataTable;

class CompaniesTable extends Component
{
    use WithDataTable, Authorize, Limit;

    public string $search = '';

    public function booted(): void
    {
        $this->authorize('admin|view companies|edit companies|view own business');
        $this->limit();
    }

    public function mount(): void
    {
        $this->sortField = 'business_name';
    }

    public function getDataSet()
    {
        return Company::where('business_name', 'like', '%' . $this->search . '%')
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->paginate($this->perPage);
    }

    public function render()
    {
        $items = $this->getDataSet();
        return view('companies::companies_table', compact('items'))->layout('companies::admin');
    }
}
