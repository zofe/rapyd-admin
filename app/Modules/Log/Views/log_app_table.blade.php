<div>
    @if($summary)
        <x-rpd::card title="Most frequent errors">
            <table class="table table-sm small mb-0">
                @foreach($summary as $row)
                    <tr>
                        <td class="text-nowrap"><span class="badge bg-{{ \Zofe\Rapyd\Modules\Log\Services\LogParser::LEVEL_CLASSES[$row['level']] ?? 'secondary' }}">{{ $row['count'] }}&times;</span></td>
                        <td class="text-truncate" style="max-width: 60vw">
                            <a href="#" wire:click.prevent="showStack('{{ $row['id'] }}')">{{ \Illuminate\Support\Str::limit($row['message'], 160) }}</a>
                        </td>
                        <td class="text-nowrap text-end text-muted">{{ $row['last_seen'] }}</td>
                    </tr>
                @endforeach
            </table>
        </x-rpd::card>
    @endif

    <x-rpd::card>
        <x-rpd::table title="App Logs" :items="$items">
            <x-slot name="filters">
                <x-rpd::select col="col-auto" model="logFile" :options="array_combine($logFiles, $logFiles)" placeholder="log file..." />
                <x-rpd::select col="col-auto" model="level" :options="array_combine($levels, $levels)" placeholder="level..." addempty />
                <x-rpd::input col="col" debounce="300" model="search" placeholder="search..." />
            </x-slot>
            <x-slot name="buttons">
                <a href="{{ route_lang('log.app') }}" class="btn btn-outline-dark">Reset</a>
            </x-slot>

            <table class="table table-sm">
                <thead>
                <tr>
                    <th>Level</th>
                    <th>Date</th>
                    <th>Context</th>
                    <th>Message</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($items as $log)
                    <tr>
                        <td class="text-nowrap"><span class="badge bg-{{ $log['level_class'] }}">{{ $log['level'] }}</span></td>
                        <td class="text-nowrap small">{{ $log['date'] }}</td>
                        <td class="small">{{ $log['context'] }}</td>
                        <td class="small text-break">{{ \Illuminate\Support\Str::limit($log['text'], 300) }}</td>
                        <td class="text-end">
                            @if($log['has_stack'])
                                <x-rpd::icon name="list" click="showStack('{{ $log['id'] }}')" />
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </x-rpd::table>
    </x-rpd::card>

    <x-rpd::modal name="logStack" title="Stack trace" size="xl">
        @if($stack)
            <div class="mb-2">
                <span class="badge bg-{{ $stack['level_class'] }}">{{ $stack['level'] }}</span>
                <span class="small text-muted">{{ $stack['date'] }} &middot; {{ $stack['context'] }}</span>
            </div>
            <p class="small fw-bold text-break">{{ $stack['text'] }}</p>
            <pre class="small" style="max-height: 55vh; overflow: auto; white-space: pre-wrap;">{{ $stack['stack'] }}</pre>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    x-on:click="navigator.clipboard.writeText($el.parentElement.querySelector('pre').textContent).then(() => { $el.textContent = 'Copied'; setTimeout(() => $el.textContent = 'Copy', 1500); })">Copy</button>
        @endif
    </x-rpd::modal>
</div>
