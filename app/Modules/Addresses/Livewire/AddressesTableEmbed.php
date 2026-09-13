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

    /** Selectable: a radio on each row; the chosen id is dispatched as `selectedAddress` (checkout, order forms…). */
    public bool $selectable = false;

    public ?string $selected = null;

    public $addresses;

    public function mount(string $addressableType, string $addressableId, bool $editable = false, bool $selectable = false, ?string $selected = null): void
    {
        $modelClass = Relation::getMorphedModel($addressableType) ?? $addressableType;
        abort_unless(class_exists($modelClass), 404);

        $this->entity = $modelClass::findOrFail($addressableId);
        $this->editable = $editable;
        $this->selectable = $selectable;
        $this->selected = $selected;
        $this->authorize('admin|edit everything|edit users|edit own users|edit own business', $this->entity);
        $this->refreshAddresses();
    }

    #[On('savedAddress')]
    public function refreshAddresses(): void
    {
        $this->addresses = $this->entity->addresses()->get();

        if ($this->selectable) {
            $ids = $this->addresses->pluck('id')->map(fn ($id) => (string) $id);
            if (! $this->selected || ! $ids->contains($this->selected)) {
                // default: the first address with a country (older rows may have none), else the first one
                $first = $this->addresses->firstWhere('country_code', '!=', null) ?? $this->addresses->first();
                $this->select($first?->id);
            }
        }
    }

    public function select(?string $addressId): void
    {
        $this->selected = $addressId ? (string) $addressId : null;
        $this->dispatch('selectedAddress', addressId: $this->selected);
    }

    public function render()
    {
        return view('addresses::addresses_table_embed', ['addresses' => $this->addresses]);
    }
}
