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
                            $role = $user->pivot->role ?? 'member';
                            $badgeColor = $role === 'owner' ? 'primary' : 'secondary';
                        @endphp
                        <span class="badge bg-{{ $badgeColor }}">{{ ucfirst($role) }}</span>

                        @if($editable)
                            <x-rpd::icon name="edit" click="$dispatch('editUser', {userId: '{{ $user->id }}', companyId: '{{ $company->id }}'})" />
                            <x-rpd::icon name="trash-alt" click="$dispatch('deleteUser', {userId: '{{ $user->id }}'})" confirm="Delete {{ $user->name }}?" />
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-muted small mb-0">No users yet.</p>
    @endif
</div>
