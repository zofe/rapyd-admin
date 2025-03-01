<div>
    @if($addresses)
    <ul class="list-group list-group-flush ">
        @foreach($addresses as $address)
            <li class="list-group-item text-body p-1 d-flex justify-content-between align-items-start">
                {{ $address->address }}
                {{ $address->street_number }},
                {{ $address->zipcode }}
                {{ $address->city }}
                ({{ $address->province }}),
                {{ $address->region }} -
                {{ $address->country }}
                ({{ $address->country_code }})

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
