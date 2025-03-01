<div>
        <x-rpd::modal
            name="editAddress"
            title="Edit Address"
            action="save"
        >
            <div>
                <x-rpd::input col="col-md-12" model="address.address" label="Address" />
                <x-rpd::input col="col-md-12" model="address.city" label="City" />
                <x-rpd::input col="col-md-12" model="address.zipcode" label="Zipcode" />
            </div>
        </x-rpd::modal>


</div>
