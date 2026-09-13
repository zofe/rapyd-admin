<div>
    @if($addresses)
    <ul class="list-group list-group-flush ">
        @foreach($addresses as $address)
            <li class="list-group-item text-body p-1 d-flex justify-content-between align-items-start">
                @if($selectable)
                    {{-- wire:model (not a checked attribute): once clicked, a radio ignores attribute changes,
                         so the server's choice (after a delete, a default) must be pushed as a property --}}
                    <input type="radio" class="form-check-input me-2 mt-1 flex-shrink-0" name="selected-address-{{ $this->getId() }}"
                           value="{{ $address->id }}" wire:model.live="selected" wire:key="radio-{{ $address->id }}">
                @endif
                @php
                    $line = array_filter([
                        trim($address->address . ' ' . $address->street_number),
                        trim($address->zipcode . ' ' . $address->city . ($address->province ? " ({$address->province})" : '')),
                        $address->region,
                        trim($address->country . ($address->country_code ? " ({$address->country_code})" : '')),
                    ]);
                @endphp
                <span class="flex-grow-1 {{ $selectable ? 'cursor-pointer' : '' }}" @if($selectable) wire:click="select('{{ $address->id }}')" role="button" @endif>{{ implode(', ', $line) }}</span>

                @if($editable)
                    <div class="text-nowrap small">
                        <x-rpd::icon name="edit" click="$dispatch('editAddress',{addressId: '{{$address->id}}'})" />
                        <x-rpd::icon name="trash-alt" click="$dispatch('deleteAddress',{addressId: '{{$address->id}}'})" confirm="delete address {{ $address->address }}?"  />
                    </div>
                @endif
            </li>
        @endforeach

    </ul>
    @endif



    <livewire:addresses::addresses-modal-edit-embed />

</div>
