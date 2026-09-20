<x-rpd::card>
    <x-rpd::table title="Companies" :items="$items">

        <x-slot name="filters">
            <x-rpd::input col="col" debounce="350" model="search" placeholder="search..." />
        </x-slot>

        <x-slot name="buttons">
            <a href="{{ route_lang('companies.table') }}" class="btn btn-outline-dark">{{ __('Reset') }}</a>
            <a href="{{ route_lang('companies.edit') }}" class="btn btn-outline-primary">{{ __('Add') }}</a>
        </x-slot>

        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Id') }}</th>
                    <th><x-rpd::sort model="business_name" label="Business Name" /></th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Tier') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th><x-rpd::sort model="created_at" label="Created" /></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $company)
                    <tr>
                        <td>
                            <x-rpd::nav-link :label="$company->shortId" route="companies.view" :params="$company->id" />
                        </td>
                        <td>{{ $company->business_name }}</td>
                        <td>{{ $company->email }}</td>
                        <td>{{ $company->tier }}</td>
                        <td>
                            <span class="badge bg-{{ $company->status === 'active' ? 'success' : 'secondary' }}">
                                {{ $company->status }}
                            </span>
                        </td>
                        <td><x-rpd::date-formatted :date="$company->created_at" /></td>
                        <td class="text-end">
                            <x-rpd::icon name="edit" route="companies.edit" :params="$company->id" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </x-rpd::table>
</x-rpd::card>
