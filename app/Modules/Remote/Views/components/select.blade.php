{{-- <x-remote::select resource="crm.customers" wire:model="customer_id" :value="$customer_id" label="Customer" /> --}}
@props(['resource', 'value' => null])
@php($current = \Zofe\Rapyd\Modules\Remote\Support\Remote::find($resource, $value))
<x-rpd::select-list
    :options="$current ? [$current->remote_id => $current->label] : []"
    endpoint="{{ route('remote.lookup', explode('.', $resource, 2)) }}"
    {{ $attributes }}
/>
