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
                <th>
                    <x-rpd::sort model="id" label="id" />
                </th>
                <th>{{__('auth::user.firstname')}}</th>
                <th>{{__('auth::user.email')}}</th>
                <th>{{__('auth::user.roles')}}</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $user)
            <tr>
                <td>
                    <a href="{{ route_lang('auth.users.view',$user->id) }}">{{ $user->id }}</a>
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
            </tr>
            @endforeach
            </tbody>
        </table>

    </x-rpd::table>
</x-rpd::card>

