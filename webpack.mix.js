
let path = require('path');
let mix = require('laravel-mix');


mix.setPublicPath('public');
mix.setResourceRoot('./');
mix.js("resources/js/rapyd.js", "public")
    .js("resources/js/bootstrap.js", "public")
    //.js("resources/js/alpine.js", "public")
    .sass('resources/sass/rapyd.scss', 'public').sourceMaps(true, 'source-map')
    .sass('resources/sass/bootstrap.scss', 'public').sourceMaps(true, 'source-map')
