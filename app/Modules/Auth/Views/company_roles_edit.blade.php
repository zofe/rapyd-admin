
<x-rpd::card>
    <div>
        <x-slot name="buttons">
        </x-slot>

        <x-rpd::edit title="Permissions Edit">
            <div>
                <div class="row mb-1">
                    <div class="col-lg-6">
                        <label class="col-form-label">Role</label>
                        <div class="text-dark text-ucfirst">{{ $role->name }}</div>
                    </div>
                </div>
                <div class="row mb-5">

                    <x-rpd::select-list model="permissions" multiple :options="$available_permissions" label="Permissions" />

                </div>
            </div>
            <x-slot name="actions">

                <button type="submit" class="btn btn-primary">Save</button>
            </x-slot>
        </x-rpd::edit>

    </div>
</x-rpd::card>




