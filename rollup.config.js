
import nodeResolve from '@rollup/plugin-node-resolve';
import livereload from 'rollup-plugin-livereload';
import svelte from 'rollup-plugin-svelte';
import sveltePreprocess from 'svelte-preprocess';

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
            },
        ),

        // reloading compiled assets in the browser
        process.env.ROLLUP_WATCH && livereload('public'),

        svelte(
            {
                // enable run-time checks when not in production
                dev: !isBuildProduction,

                hydratable: true,

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

                // we'll extract any component CSS out into
                // a separate file - better for performance
                css: css => {
                    css.write('public/build/app.css', !isBuildProduction);
                },
            },
        ),
    ],

    watch: {
        clearScreen: false,
    },
};
