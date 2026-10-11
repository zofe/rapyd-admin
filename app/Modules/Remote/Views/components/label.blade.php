{{-- <x-remote::label resource="crm.customers" :id="$device->customer_id" /> --}}
@props(['resource', 'id' => null])
{{ \Zofe\Rapyd\Modules\Remote\Support\Remote::find($resource, $id)?->label ?? ($id ? "#{$id}" : '') }}
