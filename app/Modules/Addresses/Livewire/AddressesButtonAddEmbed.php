<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;
use Illuminate\Database\Eloquent\Relations\Relation;


class AddressesButtonAddEmbed extends Component
{
    use Authorize;

    public $entity;

    public function booted()
    {
        $this->authorize('admin|edit addresses');
    }

    public function mount(string $addressableType, string $addressableId)
    {
        $modelClass = Relation::getMorphedModel($addressableType) ?? $addressableType;
        if (!$modelClass) {
            abort(404, "Invalid addressable type");
        }
        $this->entity = $modelClass::findOrFail($addressableId);
    }

    public function render()
    {
        return view('addresses::addresses_button_add_embed');
    }
}
