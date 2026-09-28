<div>
    @if($users && $users->count())
        <ul class="list-group list-group-flush">
            @foreach($users as $user)
                <li class="list-group-item px-0 py-1 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-medium">{{ $user->name }}</span>
                        <span class="text-muted small ms-2">{{ $user->email }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @php
                            $role = $user->company_role ?: 'member';
                            $badgeColor = $role === 'owner' ? 'primary' : 'secondary';
                        @endphp
                        <span class="badge bg-{{ $badgeColor }}">{{ ucfirst($role) }}</span>

                        @php
                            // the same rule the component enforces (UsersModalEditEmbed): show only what the viewer may do
                            $me = auth()->user();
                            $isSuper = $me && $me->hasAnyRole(config('rapyd.auth.super_admin_roles', ['admin']));
                            $canEdit = $me && ($isSuper || ((string) $me->company_id === (string) $company->id
                                && ($me->company_role === 'owner' || (string) $me->getKey() === (string) $user->getKey())));
                            $canDelete = $canEdit && (string) $me->getKey() !== (string) $user->getKey() && ($isSuper || $role !== 'owner');
                        @endphp
                        @if($editable && $canEdit)
                            <x-rpd::icon name="edit" click="$dispatch('editUser', {userId: '{{ $user->id }}', companyId: '{{ $company->id }}'})" />
                        @endif
                        @if($editable && $canDelete)
                            <x-rpd::icon name="trash-alt" click="$dispatch('deleteUser', {userId: '{{ $user->id }}'})" confirm="Delete {{ $user->name }}?" />
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-muted small mb-0">{{ __('No users yet.') }}</p>
    @endif
</div>
