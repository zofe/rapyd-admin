<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Component;

class AddressesTableEmbed extends Component
{
    use Authorize;

    public $entity;

    public bool $editable = false;

    public $addresses;

    public function mount(string $addressableType, string $addressableId, bool $editable = false): void
    {
        $modelClass = Relation::getMorphedModel($addressableType) ?? $addressableType;
        abort_unless(class_exists($modelClass), 404);

        $this->entity = $modelClass::findOrFail($addressableId);
        $this->editable = $editable;
        $this->authorize('admin|edit everything|edit users|edit own users|edit own business', $this->entity);
        $this->refreshAddresses();
    }

    #[On('savedAddress')]
    public function refreshAddresses(): void
    {
        $this->addresses = $this->entity->addresses()->get();
    }

    public function render()
    {
        return view('addresses::addresses_table_embed', ['addresses' => $this->addresses]);
    }
}
