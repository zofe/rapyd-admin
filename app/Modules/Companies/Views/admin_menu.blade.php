@if(Auth::user() && Auth::user()->hasRoleOrPermission('admin|view everything|edit everything|view companies|edit companies'))
    <x-rpd::nav-dropdown icon="address-card" label="Companies" active="/companies">
        <x-rpd::nav-link label="Companies" route="companies.table" type="collapse-item" />
    </x-rpd::nav-dropdown>
@endif
