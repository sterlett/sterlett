
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { APPLICATION_PAGE_TITLE } from '@./Context.js';
    import { getContext } from 'svelte';

    export let location;

    const title = $format('About');

    const pageTitleStore = getContext(APPLICATION_PAGE_TITLE);
    pageTitleStore.set(title);
</script>

<template src="./AboutPage.spectre.html"></template>
