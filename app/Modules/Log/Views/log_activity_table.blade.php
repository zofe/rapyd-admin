<div>
    <x-rpd::card>
        <x-rpd::table title="Activity" :items="$items">
            <x-slot name="filters">
                <x-rpd::date col="col-auto" model="date_from" label="From" />
                <x-rpd::date col="col-auto" model="date_to" label="To" />
                <x-rpd::select-list col="col" model="user" label="Author" multiple :options="$users" placeholder="any" />
                <x-rpd::select-list col="col" model="log_name" label="Event" multiple :options="$log_names" placeholder="any" />
                <x-rpd::input col="col" debounce="300" model="search" label="Search" placeholder="text..." />
            </x-slot>
            <x-slot name="buttons">
                <a href="{{ route_lang('log.activity') }}" class="btn btn-outline-dark">{{ __('Reset') }}</a>
            </x-slot>

            <table class="table table-sm">
                <thead>
                <tr>
                    <th><x-rpd::sort model="created_at" label="When" /></th>
                    <th>{{ __('Author') }}</th>
                    <th><x-rpd::sort model="log_name" label="Event" /></th>
                    <th>{{ __('Description') }}</th>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Changes') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($items as $activity)
                    <tr>
                        <td class="text-nowrap small">{{ $activity->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="small">{{ $activity->causer?->name ?? 'system' }}</td>
                        <td><span class="badge bg-secondary">{{ $activity->log_name }}</span></td>
                        <td class="small">{{ $activity->description }}</td>
                        <td class="small text-nowrap">
                            @if($activity->subject_type)
                                {{ class_basename($activity->subject_type) }}
                                <code>{{ $activity->subject?->shortId ?? substr((string) $activity->subject_id, -8) }}</code>
                            @endif
                        </td>
                        <td class="small">
                            @foreach($activity->readableChanges() as $change)
                                <div class="text-truncate" style="max-width: 28vw">
                                    <span class="text-muted">{{ $change['key'] }}:</span>
                                    @if($change['old'] !== null)
                                        <del class="text-muted">{{ is_scalar($change['old']) ? $change['old'] : json_encode($change['old']) }}</del> &rarr;
                                    @endif
                                    {{ is_scalar($change['new']) ? $change['new'] : json_encode($change['new']) }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-rpd::table>
    </x-rpd::card>
</div>
