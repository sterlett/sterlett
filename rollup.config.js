
import svelte from 'rollup-plugin-svelte';
import resolve from '@rollup/plugin-node-resolve';
import livereload from "rollup-plugin-livereload";

const isBuildProduction = process.env.BUILD === 'production';

export default {
    input: 'src/front/main.js',

    output: {
        sourcemap: false,
        format: 'iife',
        name: 'app',
        file: 'public/build/bundle.js'
    },

    plugins: [
        svelte({
            // enable run-time checks when not in production
            dev: !isBuildProduction,

            // we'll extract any component CSS out into
            // a separate file - better for performance
            css: css => {
                css.write('public/build/bundle.css', false);
            }
        }),

        resolve({
            browser: true,
            dedupe: ['svelte']
        }),

        // reloading compiled assets in the browser
        !isBuildProduction && livereload('public')
    ],

    watch: {
        clearScreen: false
    }
};
