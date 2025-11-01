@props([
'date',          // \Carbon\Carbon
'timestamp' => null,
'format' => 'd/m/Y H:i',
])

@php
    $ts = $timestamp ?? $date->timestamp;
    $dt = \Carbon\Carbon::createFromTimestamp($ts);
    $full = $dt->format($format);

    if (strpos($full, ' ') !== false) {
        [$main, $timePart] = explode(' ', $full, 2);
    } else {
        $main     = $full;
        $timePart = null;
    }
@endphp

<div class="d-flex g-1">
    <div>
        <span>{{ $main }}</span>
        @if($timePart)
            <small> {{ $timePart }}</small>
        @endif
    </div>
    <div class="ps-1"><small class="text-gray-500">{{ $dt->diffForHumans() }}</small></div>
</div>


