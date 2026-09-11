
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
                <div class="row mb-2">
                    <x-rpd::input col="col-6" model="psswd" label="New Password" type="password" />

                    @if(method_exists($user, 'roles'))
                        <x-rpd::select-list col="col-6" model="roles" multiple :options="$available_roles" label="Roles" />
                    @endif
                </div>
                @if($this->hasCompanies())
                    <div class="row mb-5">
                        @if($this->canEditCompany())
                            <x-rpd::select-list col="col-6" model="user.company_id" :options="$availableCompanies" placeholder="—" label="{{ __('auth::user.company') }}" />
                            <x-rpd::select-list col="col-6" model="user.company_role" :options="$companyRoles" label="{{ __('auth::user.company_role') }}" />
                        @else
                            <div class="col-6">
                                <x-rpd::label label="{{ __('auth::user.company') }}" />
                                <div class="form-control-plaintext">{{ $currentCompany?->business_name ?? '—' }}</div>
                            </div>
                            <div class="col-6">
                                <x-rpd::label label="{{ __('auth::user.company_role') }}" />
                                <div class="form-control-plaintext">{{ $currentCompany ? ($companyRoles[$user->company_role] ?? $user->company_role) : '—' }}</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <x-slot name="actions">

                <button type="submit" class="btn btn-primary">Save</button>
            </x-slot>
        </x-rpd::edit>

    </div>
</x-rpd::card>
