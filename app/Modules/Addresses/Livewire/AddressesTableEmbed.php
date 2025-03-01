<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;
use Zofe\Rapyd\Traits\WithDataTable;
use Illuminate\Database\Eloquent\Relations\Relation;


class AddressesTableEmbed extends Component
{
    use WithDataTable, Authorize;

    public $search = '';
    public $sortField = 'id';
    public $entity;
    public $editable = false;
    public $addresses;

    protected $listeners = ['savedAddress' => 'refreshAddresses'];

    public function booted()
    {
        $this->authorize('admin|edit addresses');
    }

    public function mount(string $addressableType, string $addressableId, $editable = false)
    {
        $modelClass = Relation::getMorphedModel($addressableType) ?? $addressableType;
        if (!$modelClass) {
            abort(404, "Invalid addressable type");
        }
        $this->entity = $modelClass::findOrFail($addressableId);
        $this->editable = $editable;

        $this->refreshAddresses();
    }

    public function refreshAddresses()
    {
        $this->addresses = $this->entity->addresses()->get();
    }

    public function render()
    {
        $addresses = $this->addresses;
        return view('addresses::addresses_table_embed', compact('addresses'));
    }
}
