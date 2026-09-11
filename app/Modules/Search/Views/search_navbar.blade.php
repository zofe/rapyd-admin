<div class="d-flex " id="mainsearch">

   <div x-data x-init="
    new TomSelect($refs.mySelect, {
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
" style="min-width: 200px">
        <select x-ref="mySelect" placeholder="search..." ></select>
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
