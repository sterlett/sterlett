
import Application from './Application.svelte';

const app = new Application(
    {
        target: document.body,
        hydrate: true,
    },
);

export default app;
