@props([
'value' => null,      // anything: cast to bool
'yes' => 'Yes',       // phrases, translated by Lang/{locale}.json
'no' => 'No',
])

{{-- A boolean to read, not a 1 or a 0: an icon that carries the colour and a word that carries the meaning --}}
@if((bool) $value)
    <span {{ $attributes->class(['text-success text-nowrap']) }}><i class="fas fa-check-circle me-1"></i>{{ __($yes) }}</span>
@else
    <span {{ $attributes->class(['text-body-secondary text-nowrap']) }}><i class="far fa-circle me-1"></i>{{ __($no) }}</span>
@endif
