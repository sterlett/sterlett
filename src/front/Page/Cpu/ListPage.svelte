
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { APPLICATION_PAGE_TITLE } from '@./Context';
    import { getContext } from 'svelte';

    import CpuViewer from '@Hardware/Cpu/Viewer';

    export let location;

    const title = $format('CPU ratio');

    const pageTitleStore = getContext(APPLICATION_PAGE_TITLE);
    pageTitleStore.set(title);
</script>

<template src="./ListPage.spectre.html"></template>
