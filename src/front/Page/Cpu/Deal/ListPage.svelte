
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { APPLICATION_PAGE_TITLE } from '@./Context';
    import { DEAL_VIEWER_SESSION } from '@Page/Cpu/Deal/Context';
    import { writable } from 'svelte/store';
    import { getContext, setContext } from 'svelte';

    import DealRank from '@Hardware/Rank';
    import DealViewer from '@Hardware/Cpu/Deal/Viewer';

    export let location;

    const title = $format('CPU deals');

    // parent context: page title
    const pageTitleStore = getContext(APPLICATION_PAGE_TITLE);
    pageTitleStore.set(title);

    // managing a deal viewer session (all viewer instances will use the same session, i.e. singleton behavior)
    const viewerSessionStore = writable({deals: undefined});
    setContext(DEAL_VIEWER_SESSION, viewerSessionStore);
</script>

<template src="./ListPage.spectre.html"></template>

<style src="./ListPage.spectre.scss"></style>
