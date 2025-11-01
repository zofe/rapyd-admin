@props([
'col'   => null,
'label' => null,
'placeholder' => null,
'options' => [],
'icon' => null,
'prepend' => null,
'append' => null,
'size' => null,
'help' => null,
'model' => null,
'lazy' => false,
'col'  => null,
])

@php
    $required = false;
    if($model && property_exists($this,'rules') && isset($this->rules[$model]) && stristr($this->rules[$model],'required')) {
       $required = true;
    }


    if ($lazy) $bind = 'lazy';
    else $bind = 'defer';
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $key = $attributes->get('name', $model ?? $wireModel);
    $id = $attributes->get('id', $model ?? $wireModel);
    $prefix = null;
    $options = Arr::isAssoc($options) ? $options : array_combine($options, $options);
    $attributes = $attributes->class([
        'form-select',
        'form-select-' . $size => $size,
        'rounded-end' => !$append,
        'is-invalid' => $errors->has($key),
    ])->merge([
        'id' => $id,
        'wire:model.' . $bind => $model ? $prefix . $model : null,
    ]);

    //    dd($options);
@endphp


<div class="{{$col}}" wire:ignore wire:key="ignore-{{$id}}">
    <x-rpd::label :for="$id" :label="$label" :required="$required" />

    <div class="input-group">
        <select x-data="{
		tomSelectInstance: null
		}" x-init="

	    tomSelectInstance = new TomSelect($refs.tomSelect, {
             valueField: 'id'
            ,labelField: 'title'
            ,searchField: 'title'
        @if($attributes->get('create'))
            ,create: function(input,callback){
                var url = '{{ $attributes->get('create') }}?q=' + encodeURIComponent(input);
                    fetch(url)
                    .then((response) => {
                      return response.json();
                    })
                    .then((data) => {
                      callback(data);
                    });

            }
        @endif
	    @if($attributes->get('endpoint'))
            ,load: function(query, callback) {
                var url = '{{ $attributes->get('endpoint') }}?q=' + encodeURIComponent(query);
                fetch(url)
                .then((response) => {
                  return response.json();
                })
                .then((data) => {
                  callback(data);
                });
            },
            render: {
                option: function(item, escape) {
                    return item.title;
                },
                item: function(item, escape) {
                    return item.title;
                }
            }

        @endif
	    ,onChange : function(values) {
	        $wire.set('{{$key}}', values);
	    }
	    ,onItemAdd: function(value, item) {
            this.setTextboxValue('');
            //this.refreshOptions();
        }

        });" x-ref="tomSelect" x-cloak {{ $attributes }}>
            <option value="">{{ $placeholder }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}">{!! $optionLabel !!}</option>
        @endforeach
        </select>
        <x-rpd::error :key="$key"/>
    </div>

    <x-rpd::help :label="$help"/>
</div>
@push('rapyd_scripts')
    <script>
        console.log('rerendered select-list');
    </script>
@endpush
