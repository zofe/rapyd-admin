<div>
        <x-rpd::modal
            name="editAddress"
            title="Edit Address"
            action="save"
        >
            <div class="row">
                @if($lookupEnabled)
                    <div class="col-md-12 mb-2 position-relative" x-data="{ open: false }" @click.outside="open = false">
                        <label class="form-label" for="address-lookup">{{ __('Search address') }}</label>
                        <input type="search" id="address-lookup" class="form-control" autocomplete="off"
                               placeholder="Type a street and a city…"
                               wire:model.live.debounce.400ms="lookup" @focus="open = true" @input="open = true">
                        @if(count($suggestions))
                            {{-- explicit surface colours: some themes give list groups a transparent background --}}
                            <div class="list-group position-absolute w-100 shadow border rounded" x-show="open"
                                 style="z-index: 1060; max-height: 16rem; overflow-y: auto; background: var(--bs-body-bg, #fff); color: var(--bs-body-color, #212529);">
                                @foreach($suggestions as $i => $s)
                                    <button type="button" class="list-group-item list-group-item-action py-1 small"
                                            wire:click="pick({{ $i }})" @click="open = false">
                                        {{ $s['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
                <x-rpd::input col="col-md-9" model="address.address" label="Address" />
                <x-rpd::input col="col-md-3" model="address.street_number" label="Number" />
                @if($lookupEnabled)
                    {{-- city, country and state come from the lookup only: a consolidated address --}}
                    <x-rpd::input col="col-md-8" model="address.city" label="City" readonly placeholder="pick a suggestion above" />
                    <x-rpd::input col="col-md-4" model="address.zipcode" label="Zipcode" />
                    <x-rpd::input col="col-md-8" model="address.country" label="Country" readonly placeholder="pick a suggestion above" />
                    <x-rpd::input col="col-md-4" model="address.state_code" label="State / Province" readonly />
                @else
                    <x-rpd::input col="col-md-8" model="address.city" label="City" />
                    <x-rpd::input col="col-md-4" model="address.zipcode" label="Zipcode" />
                    <x-rpd::select-list col="col-md-8" model="address.country_code" :options="$countries" placeholder="—" label="Country" />
                    <x-rpd::input col="col-md-4" model="address.state_code" label="State / Province" placeholder="e.g. CA, BA" />
                @endif
                @if($address?->verified_at)
                    <div class="col-md-12 small text-muted">
                        <i class="fas fa-check-circle text-success"></i>
                        {{ $address->confidence === 'verified' ? 'Verified' : 'Partially matched' }} by {{ $address->verified_by }}
                        on {{ $address->verified_at->format('Y-m-d') }}
                    </div>
                @endif
            </div>
        </x-rpd::modal>
</div>
