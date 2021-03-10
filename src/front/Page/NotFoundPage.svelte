
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { APPLICATION_PAGE_TITLE } from '@./Context.js';
    import { getContext } from 'svelte';

    export let location;

    const title = $format('Not Found');
    const notFoundMessage = $format('Page is not found.');

    const pageTitleStore = getContext(APPLICATION_PAGE_TITLE);
    pageTitleStore.set(title);
</script>

<template src="./NotFoundPage.spectre.html"></template>
