<x-rpd::card>
    <x-rpd::table
        title="users::user.company_permissions"
        :items="$items"
    >
        <x-slot name="filters">
            <x-rpd::input col="col" debounce="350" model="search"  placeholder="search..." />
        </x-slot>

        <x-slot name="buttons">
{{--            <a href="{{ route_lang('auth.users') }}" class="btn btn-outline-dark">Reset</a>--}}
{{--            <a href="{{ route_lang('auth.users.edit') }}" class="btn btn-outline-primary">{{ __('global.add') }}</a>--}}
        </x-slot>

        <table class="table">
            <thead>
            <tr>
                <th>
                    <x-rpd::sort model="id" label="id" />
                </th>
                <th>{{__('auth::permission.name')}}</th>
                <th>
                    {{__('auth::permission.company')}}
                </th>
                <th>
                    {{__('users::user.users')}}
                </th>
                <th>
                    {{__('auth::permission.permissions')}}
                </th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $item)
            <tr>
                <td>
                    {{ $item->shortId }}
                </td>
                <td>
                    {{ $item->name }}
                </td>
                <td class="text-nowrap">
                    <x-company.admin-link :company="$item->company"/>
                </td>
                <td>
                    <x-company.user-impersonate-link :users="$item->users"/>
                </td>
                <td>
                    @foreach($item->permissions as $permission)
                        <span class="badge rounded-pill bg-light text-dark">{{$permission}}</span>
                    @endforeach
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>

    </x-rpd::table>
</x-rpd::card>

