
import nodeResolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import alias from '@rollup/plugin-alias';
import json from '@rollup/plugin-json';
import livereload from 'rollup-plugin-livereload';
import svelte from 'rollup-plugin-svelte';
import sveltePreprocess from 'svelte-preprocess';
import postcss from 'rollup-plugin-postcss';

const isBuildProduction = process.env.BUILD === 'production';

export default {
    input: 'src/front/main.js',

    output: {
        sourcemap: !isBuildProduction,
        format: 'iife',
        name: 'app',
        file: 'public/build/app.js',
    },

    plugins: [
        nodeResolve(
            {
                dedupe: ['svelte'],
                // brings the possibility to omit file extensions for the component's imports
                extensions: ['.svelte'],
            },
        ),

        // to ensure some old code base (pre-es) is still working (e.g. for svelte-i18n internals)
        commonjs(),

        alias(
            {
                entries: [
                    {
                        find: '@.',
                        replacement: process.cwd() + '/src/front',
                    },
                    {
                        find: '@Deserialization',
                        replacement: '@./Deserialization',
                    },
                    {
                        find: '@Hardware',
                        replacement: '@./Hardware',
                    },
                    {
                        find: '@Page',
                        replacement: '@./Page',
                    },
                    {
                        find: '@Translation',
                        replacement: '@./Translation',
                    },
                    {
                        find: '@_translations',
                        replacement: '@./_translations',
                    },
                ],
            },
        ),

        // transforms .json translation files into ES6 modules, for svelte-i18n
        json(),

        // reloading compiled assets in the browser
        process.env.ROLLUP_WATCH && livereload('public'),

        svelte(
            {
                compilerOptions: {
                    // enable run-time checks when not in production
                    dev: !isBuildProduction,

                    // using a static file instead (in pair with emitCss: true)
                    css: false,

                    hydratable: true,
                },

                // this will pass styles to other rollup plugins for the further processing (CssWriter is gone);
                // they are forcing 3 ways to handle CSS in the Svelte environment (since rollup-plugin-svelte@7.0.0):
                // 1. Injecting CSS directly into .js files (css: true, emitCss: false).
                // 2. Passing CSS to other Rollup plugins (css: false, emitCss: true; our way here).
                // 3. Disable CSS completely in our app (css: false, emitCss: false).
                emitCss: true,

                preprocess: sveltePreprocess(
                    {
                        scss: {
                            prependData: `
                                @import 'spectre.css/src/variables';
                                @import 'spectre.css/src/mixins';
                            `,
                        },
                    },
                ),

                onwarn: (warning, handler) => {
                    if (warning.code === 'unused-export-let') {
                        // pages have export properties, for compatibility with the route component (svelte-routing),
                        // placed for external reference only, but they neither will be declared as constants
                        // nor deleted completely, we will suppress that
                        if (warning.filename.includes('src/front/Page')) {
                            return;
                        }
                    }

                    if (warning.code === 'css-unused-selector') {
                        // table component have some styles (e.g. for active rows), which are not explicitly used
                        // and we are free to suppress here
                        if (warning.filename.includes('src/front/Hardware/Representation/Table.svelte')) {
                            return;
                        }
                    }

                    handler(warning);
                },
            },
        ),

        // a way to process CSS output from the Svelte compiler - with sourcemaps (rollup-plugin-postcss),
        // after they have offloaded all CSS processing to the Rollup's side (since rollup-plugin-svelte@7.0.0);
        // we have already done some CSS preprocess passes at this point (using svelte-preprocess),
        // so we just tell the bundler here to create a file for our styles "as is", after they are being emitted
        // by the Svelte plugin (emitCss: true)
        // see: https://github.com/sveltejs/rollup-plugin-svelte/blob/master/CHANGELOG.md#700
        postcss(
            {
                extract: true,      // will create 'app.css'
                sourceMap: !isBuildProduction ? 'inline' : false,
            },
        ),

        // a backup way to process CSS output (rollup-plugin-css-only) - no sourcemaps
        // cssOnly({ output: 'app.css' }),
    ],

    watch: {
        clearScreen: false,
    },
};
