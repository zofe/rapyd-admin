<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Component;

class AddressesButtonAddEmbed extends Component
{
    use Authorize;

    public $entity;

    public function mount(string $addressableType, string $addressableId): void
    {
        $modelClass = Relation::getMorphedModel($addressableType) ?? $addressableType;
        abort_unless(class_exists($modelClass), 404);

        $this->entity = $modelClass::findOrFail($addressableId);
        $this->authorize('admin|edit everything|edit users|edit own users|edit own business', $this->entity);
    }

    public function render()
    {
        return view('addresses::addresses_button_add_embed');
    }
}
