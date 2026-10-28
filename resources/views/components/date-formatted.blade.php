@props([
'date' => null,       // \Carbon\Carbon, a DateTime or a string Carbon can parse; null prints nothing
'timestamp' => null,
'format' => 'd/m/Y H:i',
'ago' => true,        // the "3 days ago" after the date
])

@php
    $dt = null;
    if ($timestamp !== null) {
        $dt = \Carbon\Carbon::createFromTimestamp($timestamp);
    } elseif ($date instanceof \DateTimeInterface) {
        $dt = \Carbon\Carbon::instance($date);
    } elseif (filled($date)) {
        try { $dt = \Carbon\Carbon::parse($date); } catch (\Throwable) { $dt = null; }
    }

    $main = $timePart = null;
    if ($dt) {
        $full = $dt->format($format);
        [$main, $timePart] = array_pad(explode(' ', $full, 2), 2, null);
    }
@endphp

@if($dt)
<div class="d-flex g-1">
    <div>
        <span>{{ $main }}</span>
        @if($timePart)
            <small> {{ $timePart }}</small>
        @endif
    </div>
    @if($ago)
        <div class="ps-1"><small class="text-gray-500">{{ $dt->diffForHumans() }}</small></div>
    @endif
</div>
@endif
