@php $title = $company->exists ? 'Update Company' : 'Create Company'; @endphp

<x-rpd::card>
    <x-rpd::edit :title="$title">

        <x-slot name="buttons">
            @if($company->exists)
                <a href="{{ route_lang('companies.view', $company->id) }}" class="btn btn-outline-dark btn-sm">Back</a>
            @else
                <a href="{{ route_lang('companies.table') }}" class="btn btn-outline-dark btn-sm">Back</a>
            @endif
        </x-slot>

        <div class="row">
            <x-rpd::input col="col-md-6" model="company.business_name" label="Business Name" />
            <x-rpd::input col="col-md-6" model="company.email" label="Email" type="email" />
            <x-rpd::input col="col-md-4" model="company.vat" label="VAT" />
            <x-rpd::input col="col-md-4" model="company.phone" label="Phone" />
            <x-rpd::input col="col-md-4" model="company.tier" label="Tier" />
            <x-rpd::select-list col="col-md-4" model="company.status" label="Status"
                :options="['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended']" />
            <x-rpd::input col="col-12" model="company.note" label="Note" />
        </div>

        <x-slot name="actions">
            <button type="submit" class="btn btn-primary">Save</button>
        </x-slot>

    </x-rpd::edit>
</x-rpd::card>
