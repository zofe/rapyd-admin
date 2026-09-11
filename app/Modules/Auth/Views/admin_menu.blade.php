@if(Auth::user() && Auth::user()->hasRoleOrPermission('admin|view everything|edit everything|view users|edit users|view companies|edit companies|view own business|edit own business'))
<x-rpd::nav-dropdown icon="user" label="Auth" active="/auth|/companies">
    @if(Auth::user()->hasRoleOrPermission('admin|view everything|edit everything|view users|edit users'))
        <x-rpd::nav-link label="Users" route="auth.users" type="collapse-item" />
    @endif
    @if(Route::has('companies.table') && Auth::user()->hasRoleOrPermission('admin|view everything|edit everything|view companies|edit companies|view own business|edit own business'))
        <x-rpd::nav-link label="Companies" route="companies.table" type="collapse-item" />
    @endif
    @if(Auth::user()->hasRoleOrPermission('admin|view everything|edit everything|view users|edit users'))
        <x-rpd::nav-link label="Role&Permissions" route="auth.permissions" type="collapse-item" />
    @endif
</x-rpd::nav-dropdown>
@endif
