<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Addresses\Models\Address;
use Zofe\Rapyd\Support\Countries;

class AddressesModalEditEmbed extends Component
{
    use Authorize;

    public $address;

    protected $rules = [
        'address.address' => 'required|string|max:255',
        'address.city'    => 'required|string|max:255',
        'address.zipcode' => 'required|string|max:20',
        'address.country_code' => 'required|string|size:2',
        'address.state_code'   => 'nullable|string|max:10',
        // kept in the rules so Livewire carries them across requests on an unsaved model
        'address.addressable_type' => 'nullable',
        'address.addressable_id'   => 'nullable',
    ];

    public function booted(): void
    {
        $this->authorize('admin|edit everything|edit users|edit own users|edit own business');
    }

    #[On('editAddress')]
    public function editAddress(?string $addressId = null, ?string $addressableType = null, ?string $addressableId = null): void
    {
        $this->address = $addressId ? (Address::find($addressId) ?? new Address()) : new Address();

        if (! $this->address->exists && $addressableType && $addressableId) {
            $this->address->addressable_type = $addressableType;
            $this->address->addressable_id = $addressableId;
        }

        $this->authorizeOwner();
        $this->dispatch('show-modal', ['editAddress']);
    }

    public function save(): void
    {
        $this->validate();
        $this->authorizeOwner();
        $this->address->country_code = strtoupper($this->address->country_code);
        $this->address->country = Countries::name($this->address->country_code);
        $this->address->state_code = $this->address->state_code ? strtoupper($this->address->state_code) : null;
        $this->address->save();

        $this->dispatch('hide-modals');
        $this->dispatch('savedAddress');
    }

    #[On('deleteAddress')]
    public function deleteAddress(string $addressId): void
    {
        $this->address = Address::findOrFail($addressId);
        $this->authorizeOwner();
        $this->address->delete();

        $this->dispatch('savedAddress');
    }

    // The address belongs to a user or a company: the entity-level checks
    // (e.g. CompanyAuth) decide who may touch its addresses.
    protected function authorizeOwner(): void
    {
        $type = $this->address->addressable_type;
        $modelClass = $type ? (Relation::getMorphedModel($type) ?? $type) : null;
        $owner = $modelClass && class_exists($modelClass) ? $modelClass::find($this->address->addressable_id) : null;

        abort_unless($owner, 404);
        $this->authorize('admin|edit everything|edit users|edit own users|edit own business', $owner);
    }

    public function render()
    {
        return view('addresses::addresses_modal_edit_embed', ['countries' => Countries::all()]);
    }
}
