<div>
        <x-rpd::modal
            name="editAddress"
            title="Edit Address"
            action="save"
        >
            <div class="row">
                <x-rpd::input col="col-md-12" model="address.address" label="Address" />
                <x-rpd::input col="col-md-8" model="address.city" label="City" />
                <x-rpd::input col="col-md-4" model="address.zipcode" label="Zipcode" />
                <x-rpd::select-list col="col-md-8" model="address.country_code" :options="$countries" placeholder="—" label="Country" />
                <x-rpd::input col="col-md-4" model="address.state_code" label="State / Province" placeholder="e.g. CA, BA" />
            </div>
        </x-rpd::modal>
</div>
