<div class="d-flex " id="mainsearch">

   <div x-data="{ ts: null, typing: false }" x-init="
    ts = new TomSelect($refs.mySelect, {
        shouldLoad: function(query) { return query.length > 0; }
        ,onType: function(query) {
            typing = query.length > 0;
            if (!query.length) { this.clearOptions(); this.close(); }
        }
        ,
        valueField: 'id'
        ,labelField: 'html'
        ,searchField: 'html'
        ,load: function(query, callback) {
            if (!query.length) return callback();
            fetch('{{ route_lang('search.items') }}?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(json => {
                    callback(json);
                })
                .catch(() => callback());
        },
        render: {
            option: function(item, escape) {
                return item.html;
            },
            item: function(item, escape) {
                return item.html;
            }
        },
        onChange: function(value) {
            if (!value) return;
            var option = this.options[value];
            if (option && option.url) {
                window.location.href = option.url;
            }
        }
    })
" style="min-width: 200px" class="position-relative">
        <select x-ref="mySelect" placeholder="search..." ></select>
        <button type="button" x-show="typing" x-cloak
                @click="ts.setTextboxValue(''); ts.clearOptions(); ts.close(); typing = false"
                class="btn btn-link btn-sm text-muted position-absolute top-50 translate-middle-y" style="right: 28px; padding: 0 4px; z-index: 5;" aria-label="clear search">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <style>

        .ts-wrapper:not(.form-control,.form-select).single .ts-control {
            background-image: none;
        }
        .ts-control .ts-dropdown {
            background: transparent;
            border: none;
        }
        #mainsearch .ts-control::after {
            font-family: "Font Awesome 5 Free";
            content: "\f002";
            font-weight: 900;
            display: inline-block;
            width: 16px;
            height: 16px;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }
    </style>
</div>
