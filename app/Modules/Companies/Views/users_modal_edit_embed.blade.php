<div>
    <x-rpd::modal name="editUser" title="Edit User" action="save">
        <div>
            <x-rpd::input inline model="user.name" label="Name" />
            <x-rpd::input inline model="user.email" label="Email" type="email" />
            <x-rpd::input inline model="passwd" label="Password" type="password" />
            <x-rpd::select-list inline model="role" label="Role" :options="$availableRoles" />
        </div>
    </x-rpd::modal>
</div>
