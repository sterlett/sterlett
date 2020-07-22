
import svelte from 'rollup-plugin-svelte';

const production = !process.env.ROLLUP_WATCH;

export default {
    input: 'src/front/main.js',
    output: {
        sourcemap: true,
        format: 'iife',
        name: 'app',
        file: 'public/build/bundle.js'
    },
    plugins: [
        svelte({
            // enable run-time checks when not in production
            dev: !production,

            // we'll extract any component CSS out into
            // a separate file - better for performance
            css: css => {
                css.write('public/build/bundle.css');
            }
        })
    ],
    watch: {
        clearScreen: false
    }
};
