<div>
<div class="row">
    <div class="col-md-7">
        <x-rpd::card>
            <x-rpd::view title="User Detail">
              <x-slot name="buttons">
                <a href="{{ route_lang('auth.users') }}" class="btn btn-outline-primary">list</a>
                <a href="{{ route_lang('auth.users.edit',$user->id) }}" class="btn btn-outline-primary">edit</a>
              </x-slot>

              <dl class="row">
                <dt class="col-3">Name</dt>
                <dd class="col-9">{{ $user->name }}</dd>
                <dt class="col-3">Email</dt>
                <dd class="col-9">{{ $user->email }}</dd>
                <dt class="col-3">Roles</dt>
                <dd class="col-9">
                        {{ optional(optional($user->roles)->pluck('name'))->join(',') }}
                </dd>
              </dl>

            </x-rpd::view>
        </x-rpd::card>
    </div>

    @if($this->hasCompanies())
    <div class="col-md-5">
        <x-rpd::card title="{{ __('auth::user.company') }}">
            @if($user->company)
                <dl class="row mb-0">
                    <dt class="col-4">{{ __('auth::user.company') }}</dt>
                    <dd class="col-8">
                        <x-rpd::nav-link :label="$user->company->business_name" route="companies.view" :params="$user->company_id" />
                    </dd>
                    <dt class="col-4">{{ __('auth::user.company_role') }}</dt>
                    <dd class="col-8">
                        <span class="badge bg-{{ $user->company_role === 'owner' ? 'primary' : 'secondary' }}">{{ ucfirst($user->company_role ?: 'member') }}</span>
                    </dd>
                </dl>
            @else
                <p class="text-muted small mb-0">{{ __('auth::user.no_company') }}</p>
            @endif
        </x-rpd::card>
    </div>
    @endif
</div>
</div>
