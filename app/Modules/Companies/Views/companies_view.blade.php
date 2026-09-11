<div>
<div class="row">
    <div class="col-md-7">

        <x-rpd::card title="Company Detail">
            <x-slot name="buttons">
                <a href="{{ route_lang('companies.table') }}" class="btn btn-outline-dark btn-sm">Back</a>
                <a href="{{ route_lang('companies.edit', $company->id) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            </x-slot>

            <dl class="row">
                <dt class="col-4">Business Name</dt>
                <dd class="col-8">{{ $company->business_name }}</dd>

                <dt class="col-4">Email</dt>
                <dd class="col-8">{{ $company->email }}</dd>

                @if($company->vat)
                    <dt class="col-4">VAT</dt>
                    <dd class="col-8">{{ $company->vat }}</dd>
                @endif

                @if($company->phone)
                    <dt class="col-4">Phone</dt>
                    <dd class="col-8">{{ $company->phone }}</dd>
                @endif

                @if($company->tier)
                    <dt class="col-4">Tier</dt>
                    <dd class="col-8"><span class="badge bg-secondary">{{ $company->tier }}</span></dd>
                @endif

                <dt class="col-4">Status</dt>
                <dd class="col-8">
                    <span class="badge bg-{{ $company->status === 'active' ? 'success' : 'secondary' }}">
                        {{ $company->status }}
                    </span>
                </dd>

                @if($company->parentCompany)
                    <dt class="col-4">Parent</dt>
                    <dd class="col-8">
                        <x-rpd::nav-link :label="$company->parentCompany->business_name" route="companies.view" :params="$company->parent_id" />
                    </dd>
                @endif
            </dl>
        </x-rpd::card>

        @if($company->children->count())
            <x-rpd::card title="Sub-companies">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($company->children as $child)
                            <tr>
                                <td>{{ $child->business_name }}</td>
                                <td><span class="badge bg-{{ $child->status === 'active' ? 'success' : 'secondary' }}">{{ $child->status }}</span></td>
                                <td class="text-end"><x-rpd::icon name="edit" route="companies.view" :params="$child->id" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-rpd::card>
        @endif

    </div>
    <div class="col-md-5">

        <x-rpd::card title="Users">
            <x-slot name="buttons">
                <livewire:companies::users-button-add-embed :companyId="$company->id" />
            </x-slot>

            <livewire:companies::users-table-embed
                :companyId="$company->id"
                :editable="true"
            />
        </x-rpd::card>

        @if(config('rapyd.addresses.enabled', true))
            <x-rpd::card title="Addresses">
                <x-slot name="buttons">
                    <livewire:addresses::addresses-button-add-embed addressableType="company" :addressableId="$company->id" />
                </x-slot>

                <livewire:addresses::addresses-table-embed
                    addressableType="company"
                    :addressableId="$company->id"
                    :editable="true"
                />
            </x-rpd::card>
        @endif

    </div>
</div>

<livewire:companies::users-modal-edit-embed />
</div>
