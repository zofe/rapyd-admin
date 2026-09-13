<?php

namespace App\Modules\Addresses\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\Attributes\On;
use Livewire\Component;
use Zofe\Rapyd\Modules\Addresses\Lookup\AddressCandidate;
use Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressLookup;
use Zofe\Rapyd\Modules\Addresses\Lookup\Contracts\AddressValidator;
use Zofe\Rapyd\Modules\Addresses\Models\Address;
use Zofe\Rapyd\Support\Countries;

class AddressesModalEditEmbed extends Component
{
    use Authorize;

    public $address;

    /** The search box of the lookup service (config rapyd.addresses.lookup) and its suggestions. */
    public string $lookup = '';

    public array $suggestions = [];

    /** One per opening of the form: services billing by session group the calls with it. */
    public string $lookupSession = '';

    protected $rules = [
        'address.address' => 'required|string|max:255',
        'address.city'    => 'required|string|max:255',
        'address.zipcode' => 'required|string|max:20',
        'address.country_code' => 'required|string|size:2',
        'address.state_code'   => 'nullable|string|max:10',
        'address.street_number' => 'nullable|string|max:20',
        // filled by a lookup service; in the rules so Livewire keeps them on an unsaved model
        'address.province'    => 'nullable|string|max:255',
        'address.region'      => 'nullable|string|max:255',
        'address.country'     => 'nullable|string|max:255',
        'address.address_lat' => 'nullable|numeric',
        'address.address_lon' => 'nullable|numeric',
        'address.verified_by' => 'nullable|string|max:40',
        'address.verified_at' => 'nullable',
        'address.confidence'  => 'nullable|string|max:20',
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

        $this->lookup = '';
        $this->suggestions = [];
        $this->lookupSession = (string) \Illuminate\Support\Str::uuid();
        $this->authorizeOwner();
        $this->dispatch('show-modal', ['editAddress']);
    }

    public function updatedLookup(): void
    {
        $this->suggestions = mb_strlen(trim($this->lookup)) < 3
            ? []
            : array_map(fn (AddressCandidate $c) => $c->toArray(), app(AddressLookup::class)->search($this->lookup, $this->lookupSession));
    }

    /** Fill the fields from a suggestion; they stay editable. */
    public function pick(int $index): void
    {
        if (! isset($this->suggestions[$index])) {
            return;
        }
        $lookup = app(AddressLookup::class);
        $candidate = $lookup->resolve(AddressCandidate::fromArray($this->suggestions[$index]), $this->lookupSession);
        $this->address->fill(array_filter($candidate->attributes(), fn ($v) => $v !== null));
        $this->address->verified_by = $lookup->name();
        $this->address->verified_at = now();
        $this->address->confidence = $candidate->confidence;
        $this->lookup = $candidate->label;
        $this->suggestions = [];
        $this->lookupSession = (string) \Illuminate\Support\Str::uuid(); // the session ends with the details call
    }

    public function save(): void
    {
        $this->validate();
        $this->authorizeOwner();
        $this->validateExistence();
        $this->address->country_code = strtoupper($this->address->country_code);
        $this->address->country = Countries::name($this->address->country_code);
        $this->address->state_code = $this->address->state_code ? strtoupper($this->address->state_code) : null;
        $this->address->save();

        $this->dispatch('hide-modals');
        $this->dispatch('savedAddress');
    }

    /** config rapyd.addresses.validate: ask the service whether the address exists as typed. */
    protected function validateExistence(): void
    {
        $mode = config('rapyd.addresses.validate', false);
        $lookup = app(AddressLookup::class);
        if (! $mode || ! $lookup instanceof AddressValidator || ! $this->address->isDirty(['address', 'street_number', 'zipcode', 'city', 'country_code'])) {
            return;
        }

        $result = $lookup->validate($this->address);
        if (! $result) {
            return; // service down: the address is saved as typed
        }

        $this->address->verified_by = $lookup->name();
        $this->address->verified_at = now();
        $this->address->confidence = $result->confidence;
        if ($result->confidence !== AddressCandidate::UNKNOWN) {
            $this->address->address_lat = $result->lat ?? $this->address->address_lat;
            $this->address->address_lon = $result->lon ?? $this->address->address_lon;
        }

        if ($mode === 'strict' && $result->confidence === AddressCandidate::UNKNOWN) {
            throw \Illuminate\Validation\ValidationException::withMessages(['address.address' => 'Address not found: check street, number, postcode and city.']);
        }
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
        return view('addresses::addresses_modal_edit_embed', [
            'countries' => Countries::all(),
            'lookupEnabled' => app(AddressLookup::class)->name() !== 'none',
        ]);
    }
}
