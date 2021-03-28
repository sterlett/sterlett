
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { APPLICATION_PAGE_TITLE } from '@./Context';
    import { getContext } from 'svelte';

    export let location;

    const title = $format('Console API');

    const pageTitleStore = getContext(APPLICATION_PAGE_TITLE);
    pageTitleStore.set(title);
</script>

<template src="./ConsolePage.spectre.html"></template>
