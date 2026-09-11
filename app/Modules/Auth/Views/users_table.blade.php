<x-rpd::card>
    <x-rpd::table
        title="auth::user.user_list"
        :items="$items"
    >
        <x-slot name="filters">
            <x-rpd::input col="col" debounce="350" model="search"  placeholder="search..." />
        </x-slot>

        <x-slot name="buttons">
            <a href="{{ route_lang('auth.users') }}" class="btn btn-outline-dark">Reset</a>
            <a href="{{ route_lang('auth.users.edit') }}" class="btn btn-outline-primary">{{ __('auth::global.add') }}</a>
        </x-slot>

        <table class="table">
            <thead>
            <tr>
                <th>Id</th>
                <th>{{__('auth::user.firstname')}}</th>
                <th>{{__('auth::user.email')}}</th>
                <th>{{__('auth::user.roles')}}</th>
                @if($this->hasCompanies())
                    <th>{{__('auth::user.company')}}</th>
                @endif
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $user)
            <tr>
                <td>
                    <x-rpd::nav-link :label="$user->shortId" route="auth.users.view" :params="$user->id" />
                </td>
                <td>
                    @canImpersonate

                    @if($user->canBeImpersonated())
                        <a  href="{{ route('impersonate', $user->id) }}"
                            class="btn btn-xsm btn-link">
                            <span class="icon"><i class="fas fa-user-secret"></i></span>
                        </a>
                    @endif
                    @endCanImpersonate
                    {{ $user->name }}
                </td>
                <td>{{ $user->email }}</td>
                <td>{{ optional(optional($user->roles)->pluck('name'))->join(',') }}</td>
                @if($this->hasCompanies())
                    <td>
                        @if($user->company)
                            <x-rpd::nav-link :label="$user->company->business_name" route="companies.view" :params="$user->company_id" />
                            @if($user->company_role === 'owner')
                                <span class="badge bg-primary ms-1">Owner</span>
                            @endif
                        @endif
                    </td>
                @endif
                <td class="text-end">
                    <x-rpd::icon name="edit" route="auth.users.edit" :params="$user->id" />
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>

    </x-rpd::table>
</x-rpd::card>
