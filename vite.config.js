import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';

// Two bundles, one per `--mode`, each a classic (IIFE) script that the apps load
// via @rapydScripts / @rapydStyles after `vendor:publish`:
//   rapyd     → public/rapyd.js + public/rapyd.css   (layouts, components)
//   bootstrap → public/bootstrap.js + public/bootstrap.css (legacy rpd::app view)
// Fixed file names, no manifest: the published files are referenced as-is.
const entries = {
    rapyd:     'resources/js/rapyd.js',
    bootstrap: 'resources/js/bootstrap.js',
};

// livewire-sortable registers itself at load and throws when window.Livewire is
// missing; with an IIFE bundle a dynamic import() is hoisted, so wrap the plugin
// in a function and call it on livewire:init instead (see resources/js/rapyd.js).
const deferredPlugins = {
    name: 'rapyd-deferred-plugins',
    transform(code, id) {
        if (id.includes('livewire-sortable/dist/')) {
            return { code: `export default function () {\n${code}\n}`, map: null };
        }
    },
};

export default defineConfig(({ mode }) => {
    const name = entries[mode] ? mode : 'rapyd';

    return {
        plugins: [deferredPlugins],
        // Relative asset URLs: the files are served from /vendor/rapyd/, not from /.
        base: './',
        publicDir: false,
        resolve: {
            alias: {
                // bootstrap-icons refers to its fonts with a relative "./fonts" that
                // sass cannot rebase from node_modules: point it at the real folder.
                '@bootstrap-icons-fonts': fileURLToPath(new URL('./node_modules/bootstrap-icons/font/fonts', import.meta.url)),
            },
        },
        build: {
            outDir: 'public',
            emptyOutDir: false,
            manifest: false,
            sourcemap: true,
            // IIFE output would otherwise inject the CSS from the JS: keep one CSS file.
            cssCodeSplit: false,
            rollupOptions: {
                input: entries[name],
                output: {
                    format: 'iife',
                    name,
                    inlineDynamicImports: true,
                    entryFileNames: `${name}.js`,
                    assetFileNames: ({ names = [] }) => {
                        const file = names[0] ?? '';
                        if (file.endsWith('.css')) return `${name}.css`;
                        if (/\.(woff2?|ttf|eot|otf)$/.test(file)) return 'fonts/[name][extname]';
                        return '[name][extname]';
                    },
                },
            },
        },
        css: {
            preprocessorOptions: {
                scss: {
                    // Bootstrap 5.3 still relies on @import: keep the build output clean.
                    quietDeps: true,
                    silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'],
                },
            },
        },
    };
});
