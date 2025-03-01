
@if(Auth::user() && Auth::user()->hasRoleOrPermission('admin|view everything|edit everything|view users|edit users'))
<x-rpd::nav-dropdown icon="user" label="Auth" active="/auth">
    <x-rpd::nav-link label="Users list" route="auth.users" type="collapse-item" />
    <x-rpd::nav-link label="Role&Permissions" route="auth.permissions" type="collapse-item" />
</x-rpd::nav-dropdown>
@endif
