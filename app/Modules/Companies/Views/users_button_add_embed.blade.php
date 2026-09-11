<div>
    <x-rpd::button
        label="Add User"
        color="outline-primary"
        size="sm"
        click="$dispatch('editUser', {companyId: '{{ $company->id }}'})"
    />
</div>
