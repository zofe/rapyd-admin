<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Addresses\Models\Address;

use App\Modules\Auth\Traits\Authorize;
use Livewire\Component;



class AddressesModalEditEmbed extends Component
{
    use Authorize;

    public $address;

    protected $listeners = [
        'editAddress' => 'editAddress',
        'deleteAddress' => 'deleteAddress'
    ];

    protected $rules = [
        'address.address' => 'required',
        'address.zipcode' => 'required',
        'address.city' => 'required',
        'address.addressable_type' => 'nullable',
        'address.addressable_id' => 'nullable',

    ];

    public function booted()
    {
        $this->authorize('admin|edit addresses');
    }

    public function editAddress($addressId = null, $addressableType = null, $addressableId = null)
    {
        if ($addressId) {
            $this->address = Address::find($addressId) ?: new Address;
        } else {
            $this->address = new Address;

            if ($addressableType && $addressableId) {

                $this->address->addressable_type = $addressableType;
                $this->address->addressable_id = $addressableId;
            }
        }

        $this->dispatch('show-modal',['editAddress']);
    }

    public function save()
    {
        $this->validate([
            'address.address' => 'required|string|max:255',
        ]);
        $this->address->save();
        $this->dispatch('hide-modals');
        $this->dispatch('savedAddress');
    }

    public function deleteAddress($addressId)
    {
        $this->address = Address::findOrfail($addressId);
        $this->address->delete();
        $this->dispatch('savedAddress');
    }

    public function render()
    {
        return view('addresses::addresses_modal_edit_embed');
    }
}
