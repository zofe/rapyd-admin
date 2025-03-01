
<x-rpd::card>
    <div>
        <x-slot name="buttons">
        </x-slot>

        <x-rpd::edit title="User Edit">
            <div>
                <div class="row mb-2">
                    <x-rpd::input col="col-6" model="user.name" label="Name" />
                    <x-rpd::input col="col-6" model="user.email" label="E-mail" />
                </div>
                <div class="row mb-5">
                    <x-rpd::input col="col-6" model="psswd" label="New Password" type="password" />

                    @if(method_exists($user, 'roles'))
                        <x-rpd::select-list col="col-6" model="roles" multiple :options="$available_roles" label="Roles" />

                    @endif
                </div>
            </div>
            <x-slot name="actions">

                <button type="submit" class="btn btn-primary">Save</button>
            </x-slot>
        </x-rpd::edit>

    </div>
</x-rpd::card>



