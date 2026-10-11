{{--
  Multiple choice from a short remote list (services, categories, plans…):
  <x-remote::checkboxes resource="crm.services" wire:model="service_ids" label="Services" />
  The Livewire property is an array of ids; on the model, a json column cast to array.
--}}
@props(['resource', 'label' => null, 'col' => null])
@php
    $key = $attributes->whereStartsWith('wire:model')->first();
    $items = \Zofe\Rapyd\Modules\Remote\Support\Remote::search($resource, '');
@endphp
<div class="{{ $col }}">
    <x-rpd::label :label="$label"/>
    @forelse($items as $item)
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="{{ $key }}-{{ $item->remote_id }}"
                   value="{{ $item->remote_id }}" {{ $attributes }}>
            <label class="form-check-label" for="{{ $key }}-{{ $item->remote_id }}">{{ $item->label }}</label>
        </div>
    @empty
        <div class="text-muted small">{{ __('Not available') }}</div>
    @endforelse
    <x-rpd::error :key="$key"/>
</div>
