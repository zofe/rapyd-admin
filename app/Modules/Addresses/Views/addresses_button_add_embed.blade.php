<div>
    <x-rpd::button
        label="Add"
        color="outline-primary"
        click="$dispatch('editAddress', {addressableType: '{{ $entity->getMorphClass() }}', addressableId: '{{ $entity->id }}'})"
    />
</div>
