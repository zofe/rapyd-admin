<x-rpd::card>
    <x-rpd::table
        title="Permissions"
        :items="$items"
    >
        <x-slot name="filters">
        </x-slot>

        <x-slot name="buttons">
        </x-slot>

        <table class="table">
            <thead>
            <tr>
                <th>
                    <x-rpd::sort model="role_id" label="role" />
                </th>
                <th>permissions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($items as $role)
            <tr>
                <td>
                    <a href="{{ route_lang('auth.permissions.edit',$role->id) }}">{{ $role->name }}</a>
                </td>
                <td>{{ implode(', ',$role->permissions->pluck('name')->toArray()) }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>

    </x-rpd::table>
</x-rpd::card>

