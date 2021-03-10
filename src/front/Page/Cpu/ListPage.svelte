
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { APPLICATION_PAGE_TITLE } from '@./Context.js';
    import { getContext } from 'svelte';

    import CpuViewer from '@Hardware/Cpu/Viewer.svelte';

    export let location;

    const title = $format('CPUs');

    const pageTitleStore = getContext(APPLICATION_PAGE_TITLE);
    pageTitleStore.set(title);
</script>

<template src="./ListPage.spectre.html"></template>
