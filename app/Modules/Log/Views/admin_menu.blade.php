@if(Auth::user() && Auth::user()->hasRoleOrPermission('admin|view everything|view logs'))
    <x-rpd::nav-dropdown icon="eye" label="Logs" active="/log">
        <x-rpd::nav-link label="Activity" route="log.activity" type="collapse-item" />
        <x-rpd::nav-link label="App Logs" route="log.app" type="collapse-item" />
    </x-rpd::nav-dropdown>
@endif
