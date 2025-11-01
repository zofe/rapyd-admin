@props(['model' => 'metadata'])

<div
    x-data="{
    metadata: @entangle($model),
    pairs: [],
    init() {
      // costruisco l'array di key/value
      this.pairs = Object.entries(this.metadata)
                         .map(([k,v]) => ({ key:k, value:v }));
      // ad ogni modifica di pairs, ricompongo metadata
      this.$watch('pairs', (p) => {
        this.metadata = p.reduce((o, {key,value}) => {
          if (key) o[key] = value;
          return o;
        }, {});
      }, { deep: true });
    },
    add() { this.pairs.push({ key:'', value:'' }) },
    remove(i) { this.pairs.splice(i,1) }
  }"
    x-init="init()"
    x-cloak
    class="space-y-3"
>
    <template x-for="(pair, i) in pairs" :key="i">
        <div class="flex space-x-2">
            <input
                type="text"
                x-model="pairs[i].key"
                placeholder="Chiave"
                class="border px-2 py-1 flex-1"
            />
            <input
                type="text"
                x-model="pairs[i].value"
                placeholder="Valore"
                class="border px-2 py-1 flex-1"
            />
            <button
                type="button"
                @click.prevent="remove(i)"
                class="text-red-500"
            >✕</button>
        </div>
    </template>

    <button
        type="button"
        @click.prevent="add()"
        class="btn btn-outline-primary btn-sm"
    >Add</button>
</div>
