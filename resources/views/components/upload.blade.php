@props([
'label' => null,
'type' => 'text',
'icon' => null,
'prepend' => null,
'append' => null,
'size' => null,
'help' => null,
'model' => null,
'debounce' => false,
'lazy' => false,
'col'  => null,
])

@php
    //$errors = $errors ?? session()->get('errors', new Illuminate\Support\MessageBag);
    $errors = isset($errors) ? $errors : session()->get('errors', new Illuminate\Support\MessageBag);
    if ($debounce) $bind = 'live.debounce.' . (ctype_digit($debounce) ? $debounce : 150) . 'ms';
    else if ($lazy) $bind = 'lazy';
    else $bind = 'live.debounce.150ms';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix =  null;

    $attributes = $attributes->class([
        'form-control',
        'form-control-' . $size => $size,
        'rounded-end' => !$append,
        'is-invalid' => optional($errors)->has($key),
    ])->merge([
        'id' => $id,
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);
@endphp

<div class="{{$col}}"
    x-data="{ isUploading: false, progress: 0 }"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false"
    x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress"
>

    <!-- File Input -->
    <input type="file" {{ $attributes }}>

    <x-rpd::error :key="$key"/>

    <!-- Progress Bar -->
    <div x-show="isUploading">
        <progress max="100" x-bind:value="progress"></progress>
    </div>

</div>


