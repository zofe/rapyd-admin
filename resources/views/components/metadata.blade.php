{{-- Key / value editor bound to an array property (key => value): <x-rpd::metadata model="metadata" /> --}}
@props(['model' => 'metadata', 'keyPlaceholder' => 'key', 'valuePlaceholder' => 'value'])

<div
    x-data="{
        metadata: @entangle($model),
        pairs: [],
        init() {
            this.pairs = Object.entries(this.metadata || {}).map(([key, value]) => ({ key, value }));
            this.$watch('pairs', (p) => {
                this.metadata = p.reduce((o, { key, value }) => { if (key) o[key] = value; return o; }, {});
            }, { deep: true });
        },
        add() { this.pairs.push({ key: '', value: '' }) },
        remove(i) { this.pairs.splice(i, 1) }
    }"
    x-init="init()"
    x-cloak
    {{ $attributes->class(['rpd-metadata']) }}
>
    <template x-for="(pair, i) in pairs" :key="i">
        <div class="input-group input-group-sm mb-1">
            <input type="text" x-model="pairs[i].key" placeholder="{{ $keyPlaceholder }}" class="form-control" style="max-width: 40%">
            <input type="text" x-model="pairs[i].value" placeholder="{{ $valuePlaceholder }}" class="form-control">
            <button type="button" class="btn btn-outline-secondary" @click.prevent="remove(i)" aria-label="Remove"><i class="fas fa-times"></i></button>
        </div>
    </template>
    <button type="button" class="btn btn-outline-primary btn-sm" @click.prevent="add()"><i class="fas fa-plus me-1"></i>Add</button>
</div>
